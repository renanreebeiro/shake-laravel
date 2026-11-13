<?php

namespace App\Services;

use App\Models\CoverageDriveConnection;
use App\Models\EventCoverage;
use App\Models\ShakeEvent;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class SyncEventCoverage
{
    public function __construct(private CoverageDrive $drive, private CoverageMessenger $messenger) {}

    public function run(ShakeEvent $event): void
    {
        Cache::lock('event-coverage:'.$event->id, 600)->get(function () use ($event) {
            $event->refresh();
            $coverage = $event->coverage;
            if (!$coverage?->pending) {
                return;
            }

            $stage = 'Pasta no Drive';
            try {
                $roles = $coverage->roles();
                $groups = $coverage->recipients();
                if ($roles || $coverage->grants) {
                    $connection = CoverageDriveConnection::find(1);
                    if (!$connection?->parent_id) {
                        throw new RuntimeException('Conecte o Google Drive e escolha a pasta principal em Eventos.');
                    }
                    $drive = $this->drive->client($connection);
                    if ($roles) {
                        $this->prepareFolders($drive, $event, $connection->parent_id, $roles);
                    }
                    $stage = 'Acesso de upload';
                    $this->updateGrants($drive, $event, $groups);
                    $stage = 'Envio do link';
                    $this->deliver($event, $groups);
                }
                $coverage->failure_stage = $coverage->failure_message = null;
            } catch (Throwable $e) {
                $coverage->failure_stage = $stage;
                $coverage->failure_message = $e::class === RuntimeException::class
                    ? $e->getMessage() : 'O serviço não concluiu a operação. Confira a configuração e tente novamente.';
            }
            $coverage->pending = false;
            $coverage->saveOrFail();
        });
    }

    private function prepareFolders(PendingRequest $drive, ShakeEvent $event, string $parent, array $roles): void
    {
        $coverage = $event->coverage;
        $name = $event->event_date->toDateString().' - '.$event->title;
        if (!$coverage->parent_id) {
            $coverage->parent_id = $parent;
            $coverage->saveOrFail();
        }

        $this->drive->privateParent($drive, $coverage->parent_id);
        $root = $this->folder($drive, $coverage, 'event', $coverage->parent_id, $name);
        if ($coverage->folder_name !== $name) {
            $this->drive->rename($drive, $root, $name);
            $coverage->folder_name = $name;
            $coverage->saveOrFail();
        }

        // Role folders keep access isolated when a second professional is added later.
        foreach ($roles as $role) {
            $this->folder($drive, $coverage, $role, $root, EventCoverage::FOLDERS[$role]);
        }
    }

    private function folder(PendingRequest $drive, EventCoverage $coverage, string $key, string $parent, string $name): string
    {
        $folders = $coverage->folders ?? [];
        if (!isset($folders[$key])) {
            $folders[$key] = ['id' => $this->drive->reserveId($drive), 'ready' => false];
            $coverage->folders = $folders;
            $coverage->saveOrFail();
        }

        $id = $folders[$key]['id'];
        if (!$folders[$key]['ready']) {
            $this->drive->createFolder($drive, $id, $parent, $name);
            $folders[$key]['ready'] = true;
            $coverage->folders = $folders;
            $coverage->saveOrFail();
        }

        return $id;
    }

    private function updateGrants(PendingRequest $drive, ShakeEvent $event, array $groups): void
    {
        $coverage = $event->coverage;
        $grants = $coverage->grants ?? [];
        foreach ($grants as $role => $grant) {
            if (in_array($role, $groups[$grant['email']]['roles'] ?? [], true)) {
                continue;
            }
            if ($grant['permission']) {
                $this->drive->revoke($drive, $coverage->folders[$role]['id'], $grant['permission']);
            }
            unset($grants[$role]);
            $coverage->grants = $grants;
            $coverage->saveOrFail();
        }

        foreach ($groups as $email => $contact) {
            if (!$this->readyToSend($event, $contact)) {
                continue;
            }
            foreach ($contact['roles'] as $role) {
                if (isset($grants[$role])) {
                    continue;
                }
                $grants[$role] = ['email' => $email, 'permission' => $this->drive->grant($drive, $coverage->folders[$role]['id'], $email)];
                $coverage->grants = $grants;
                $coverage->saveOrFail();
            }
        }
    }

    private function deliver(ShakeEvent $event, array $groups): void
    {
        $error = null;
        foreach ($groups as $contact) {
            $coverage = $event->coverage;
            if (!$this->readyToSend($event, $contact)) {
                continue;
            }
            $fingerprint = $coverage->fingerprint($contact);
            $previous = $event->coverageDeliveries()->where('fingerprint', $fingerprint)->latest('id')->first();
            if ($previous?->status === 'sent') {
                continue;
            }
            if (in_array($previous?->status, ['sending', 'uncertain'], true)) {
                $error ??= 'O envio anterior pode ter sido aceito. Confira o canal antes de repetir.';
                continue;
            }
            if (!$this->messenger->configured($contact['channel'])) {
                $error ??= 'Configure o canal '.EventCoverage::CHANNELS[$contact['channel']].' para enviar o link.';
                continue;
            }

            $links = [];
            foreach ($contact['roles'] as $role) {
                $links[] = EventCoverage::FOLDERS[$role].': '.$coverage->folderUrl($role);
            }
            $attempt = $event->coverageDeliveries()->create([
                'fingerprint' => $fingerprint, 'professional' => $contact['name'],
                'channel' => $contact['channel'], 'status' => 'sending',
            ]);
            try {
                $sent = $this->messenger->send($event, $contact, $links);
            } catch (Throwable) {
                $attempt->update(['status' => 'uncertain']);
                $error ??= 'O canal não confirmou o envio. Confira se o profissional recebeu antes de repetir.';
                continue;
            }
            $attempt->update(['status' => $sent ? 'sent' : 'failed', 'sent_at' => $sent ? now() : null]);
            if (!$sent) {
                $error ??= 'O canal recusou a mensagem. Confira o destinatário e a configuração.';
            }
        }
        if ($error) {
            throw new RuntimeException($error);
        }
    }

    private function readyToSend(ShakeEvent $event, array $contact): bool
    {
        $coverage = $event->coverage;

        return $event->status === 'confirmed' && $event->address && $coverage->starts_at && $coverage->ends_at
            && !empty($contact['name']) && !empty($contact['channel'])
            && ($contact['channel'] !== 'whatsapp' || !empty($contact['whatsapp']));
    }
}
