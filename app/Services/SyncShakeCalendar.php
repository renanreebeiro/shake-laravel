<?php

namespace App\Services;

use App\Models\ShakeEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SyncShakeCalendar
{
    private const TIMEZONE = 'America/Belem';

    public function run(PendingRequest $google, string $calendarKey): array
    {
        if (ShakeEvent::whereNotNull('google_event_id')->where('google_calendar_key', '!=', $calendarKey)->exists()) {
            throw new RuntimeException('Já existem eventos vinculados a outra agenda.');
        }

        $remoteEvents = $this->readEvents($google);
        $counts = array_fill_keys(['importados', 'enviados', 'atualizados', 'cancelados', 'conflitos', 'ignorados'], 0);
        $existing = ShakeEvent::whereNull('google_event_id')->pluck('id')->keyBy(fn ($id) => $this->remoteId($id, $calendarKey));
        foreach ($remoteEvents as $remote) {
            $result = DB::transaction(fn () => $this->reconcile($google, $calendarKey, $remote, $existing[$remote['id']] ?? null));
            if ($result !== null) {
                $counts[$result]++;
            }
        }

        ShakeEvent::whereNull('google_event_id')->where('status', '!=', 'cancelled')->chunkById(100, function ($events) use ($google, $calendarKey, &$counts) {
            foreach ($events as $event) {
                $result = DB::transaction(fn () => $this->publish($google, $calendarKey, $event->id));
                if ($result !== null) {
                    $counts[$result]++;
                }
            }
        });

        return $counts;
    }

    private function readEvents(PendingRequest $google): array
    {
        $events = [];
        $pageToken = null;
        do {
            $query = ['showDeleted' => 'true', 'maxResults' => 2500, 'timeZone' => self::TIMEZONE];
            if ($pageToken !== null) {
                $query['pageToken'] = $pageToken;
            }
            $page = $google->get('events', $query)->throw()->json();
            foreach ($page['items'] ?? [] as $event) {
                $events[] = $event;
            }
            $pageToken = $page['nextPageToken'] ?? null;
        } while ($pageToken !== null);

        return $events;
    }

    private function reconcile(PendingRequest $google, string $calendarKey, array $remote, ?int $unlinkedId): ?string
    {
        $local = ShakeEvent::where('google_event_id', $remote['id'])->lockForUpdate()->first();
        if (!$local && $unlinkedId !== null) {
            $local = ShakeEvent::whereKey($unlinkedId)->whereNull('google_event_id')->lockForUpdate()->first();
        }

        if (($remote['status'] ?? null) === 'cancelled') {
            if (!$local || $local->status === 'cancelled') {
                return null;
            }
            $local->status = 'cancelled';
            $this->link($local, $remote, $calendarKey);

            return 'cancelados';
        }

        if (!empty($remote['recurrence']) || isset($remote['recurringEventId']) || ($remote['eventType'] ?? 'default') !== 'default') {
            return 'ignorados';
        }

        $incoming = $this->localFields($remote);
        if ($incoming === null) {
            return 'ignorados';
        }
        if (!$local) {
            $local = new ShakeEvent($incoming + ['mascot' => 'pending', 'status' => 'planning']);
            $this->link($local, $remote, $calendarKey);

            return 'importados';
        }

        $localHash = $this->hash($local);
        $incomingHash = $this->hash(new ShakeEvent($incoming));
        $remoteChanged = $incomingHash !== $local->google_synced_hash;
        $localChanged = $localHash !== $local->google_synced_hash;

        if ($localHash === $incomingHash) {
            if (($remote['etag'] ?? null) !== $local->google_etag || $localChanged || !$local->google_event_id) {
                $this->link($local, $remote, $calendarKey);
            }

            return null;
        }

        if ($remoteChanged && $localChanged) {
            return 'conflitos';
        }

        if ($remoteChanged) {
            $local->fill($incoming);
            $this->link($local, $remote, $calendarKey);

            return 'atualizados';
        }

        if ($localChanged) {
            $response = (clone $google)->withHeaders(['If-Match' => $remote['etag']])
                ->patch('events/'.rawurlencode($remote['id']).'?sendUpdates=none', $this->payload($local));
            if ($response->status() === 412 || $response->status() === 404) {
                return 'conflitos';
            }
            $this->link($local, $response->throw()->json(), $calendarKey);

            return 'enviados';
        }

        return null;
    }

    private function publish(PendingRequest $google, string $calendarKey, int $id): ?string
    {
        $event = ShakeEvent::whereKey($id)->whereNull('google_event_id')->lockForUpdate()->first();
        if (!$event || $event->status === 'cancelled') {
            return null;
        }

        $remoteId = $this->remoteId($id, $calendarKey);
        $response = $google->post('events?sendUpdates=none', $this->payload($event) + ['id' => $remoteId]);
        if ($response->status() === 409) {
            return 'conflitos';
        }
        $this->link($event, $response->throw()->json(), $calendarKey);

        return 'enviados';
    }

    private function link(ShakeEvent $event, array $remote, string $calendarKey): void
    {
        $event->google_event_id = $remote['id'];
        $event->google_calendar_key = $calendarKey;
        $event->google_etag = $remote['etag'] ?? null;
        $event->google_synced_hash = $this->hash($event);
        $event->saveOrFail();
    }

    private function localFields(array $event): ?array
    {
        $allDay = isset($event['start']['date'], $event['end']['date']);
        if (!$allDay && !isset($event['start']['dateTime'], $event['end']['dateTime'])) {
            return null;
        }

        if ($allDay) {
            $start = CarbonImmutable::parse($event['start']['date'], self::TIMEZONE);
            $end = CarbonImmutable::parse($event['end']['date'], self::TIMEZONE)->subDay();
        } else {
            $start = CarbonImmutable::parse($event['start']['dateTime'])->setTimezone(self::TIMEZONE);
            $end = CarbonImmutable::parse($event['end']['dateTime'])->setTimezone(self::TIMEZONE);
        }

        return [
            'title' => mb_substr(trim($event['summary'] ?? '') ?: 'Evento sem título', 0, 120),
            'address' => mb_substr(trim($event['location'] ?? ''), 0, 250),
            'event_date' => $start->toDateString(),
            'starts_at' => $start->format('H:i:s'),
            'ends_on' => $end->toDateString(),
            'ends_at' => $allDay ? null : $end->format('H:i:s'),
            'all_day' => $allDay,
        ];
    }

    private function payload(ShakeEvent $event): array
    {
        $date = $event->event_date->toDateString();
        $endDate = $event->ends_on?->toDateString() ?? $date;
        $details = ['summary' => $event->title, 'location' => $event->address];

        if ($event->all_day) {
            return $details + [
                'start' => ['date' => $date],
                'end' => ['date' => CarbonImmutable::parse($endDate, self::TIMEZONE)->addDay()->toDateString()],
            ];
        }

        $start = CarbonImmutable::parse($date.' '.$event->starts_at, self::TIMEZONE);
        $end = $event->ends_at ? CarbonImmutable::parse($endDate.' '.$event->ends_at, self::TIMEZONE) : $start->addHour();
        if ($end->lte($start)) {
            $end = $end->addDay();
        }

        return $details + [
            'start' => ['dateTime' => $start->toRfc3339String(), 'timeZone' => self::TIMEZONE],
            'end' => ['dateTime' => $end->toRfc3339String(), 'timeZone' => self::TIMEZONE],
        ];
    }

    private function hash(ShakeEvent $event): string
    {
        return hash('sha256', json_encode($this->payload($event), JSON_THROW_ON_ERROR));
    }

    private function remoteId(int $id, string $calendarKey): string
    {
        return 'shake'.hash_hmac('sha256', $calendarKey.':'.$id, config('app.key'));
    }
}
