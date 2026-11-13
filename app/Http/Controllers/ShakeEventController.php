<?php

namespace App\Http\Controllers;

use App\Models\ShakeEvent;
use App\Models\EventCoverage;
use App\Services\SyncEventCoverage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShakeEventController
{
    public function index(Request $request)
    {
        $view = $request->query('view') === 'kanban' ? 'kanban' : 'calendar';
        $today = CarbonImmutable::today('America/Belem')->locale('pt_BR');
        $month = $today->startOfMonth();
        $requested = $request->query('month');
        if (is_string($requested) && preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $requested)) {
            $month = CarbonImmutable::parse($requested.'-01', 'America/Belem')->locale('pt_BR');
        }

        $events = ShakeEvent::orderBy('event_date')->orderBy('starts_at')->orderBy('id');

        if ($view === 'kanban') {
            return view('events.index', ['view' => $view, 'columns' => $events->with(['coverage', 'coverageDeliveries'])->get()->groupBy('status')]);
        }

        $first = $month->startOfWeek(\Carbon\CarbonInterface::SUNDAY);
        $last = $month->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SATURDAY);
        $days = [];
        for ($day = $first; $day->lte($last); $day = $day->addDay()) {
            $days[] = $day;
        }

        $monthEvents = $events->whereBetween('event_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])->get();
        $byDate = $monthEvents->groupBy(fn (ShakeEvent $event) => $event->event_date->toDateString());

        return view('events.index', compact('view', 'today', 'month', 'days', 'monthEvents', 'byDate'));
    }

    public function create(Request $request)
    {
        $date = $request->validate(['date' => 'nullable|date_format:Y-m-d'])['date'] ?? null;
        return view('events.form', ['event' => new ShakeEvent(['event_date' => $date, 'status' => 'planning', 'mascot' => 'pending'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $coverage = $this->coverageData($request);
        $event = DB::transaction(function () use ($data, $coverage) {
            $event = ShakeEvent::create($data);
            $this->saveCoverage($event, $coverage);

            return $event;
        });
        $this->processCoverage($event);
        return redirect()->route('events.show', $event)->with('message', 'Evento cadastrado.');
    }

    public function show(ShakeEvent $event)
    {
        return view('events.show', ['event' => $event->load(['coverage', 'coverageDeliveries'])]);
    }

    public function edit(ShakeEvent $event)
    {
        return view('events.form', compact('event'));
    }

    public function update(Request $request, ShakeEvent $event)
    {
        $data = $this->validated($request);
        $coverage = $this->coverageData($request);
        $this->changeEvent($event, function () use ($event, $data, $coverage) {
            $event->update($data);
            $this->saveCoverage($event, $coverage);
        });
        return redirect()->route('events.show', $event)->with('message', 'Evento atualizado.');
    }

    public function status(Request $request, ShakeEvent $event)
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(ShakeEvent::STATUSES))]]);
        $this->changeEvent($event, fn () => $event->update($data));
        return redirect()->route('events.index', ['view' => 'kanban'])->with('message', 'Status atualizado.');
    }

    public function retryCoverage(Request $request, ShakeEvent $event)
    {
        abort_unless($event->coverage, 404);
        $this->changeEvent($event, function () use ($event, $request) {
            if ($request->boolean('confirm_resend')) {
                $event->coverageDeliveries()->whereIn('status', ['sending', 'uncertain'])->update(['status' => 'failed']);
            }
        });

        return back()->with('message', 'Nova tentativa de cobertura solicitada.');
    }

    private function changeEvent(ShakeEvent $event, callable $change): void
    {
        $changed = Cache::lock('event-coverage:'.$event->id, 600)->get(function () use ($event, $change) {
            DB::transaction(function () use ($event, $change) {
                $change();
                $event->coverage()->update(['pending' => true]);
            });

            return true;
        });
        if (!$changed) {
            throw ValidationException::withMessages(['coverage' => 'A cobertura está sendo processada. Aguarde alguns instantes e tente novamente.']);
        }
        $this->processCoverage($event);
    }

    private function processCoverage(ShakeEvent $event): void
    {
        if ($event->coverage()->exists()) {
            \Illuminate\Support\defer(fn () => app(SyncEventCoverage::class)->run($event));
        }
    }

    private function saveCoverage(ShakeEvent $event, ?array $data): void
    {
        if ($data !== null) {
            $event->coverage()->updateOrCreate([], $data);
        }
    }

    private function coverageData(Request $request): ?array
    {
        if (!$request->has('coverage')) {
            return null;
        }
        $data = $request->validate([
            'coverage' => 'required|array:type,starts_at,ends_at,deadline,same,photo,video',
            'coverage.type' => ['required', Rule::in(array_keys(EventCoverage::TYPES))],
            'coverage.starts_at' => 'nullable|date_format:H:i',
            'coverage.ends_at' => 'nullable|date_format:H:i|different:coverage.starts_at',
            'coverage.deadline' => 'nullable|date_format:Y-m-d',
            'coverage.same' => 'nullable|boolean',
            'coverage.photo' => 'nullable|array:name,email,whatsapp,channel',
            'coverage.video' => 'nullable|array:name,email,whatsapp,channel',
            'coverage.*.name' => 'nullable|string|max:120',
            'coverage.*.email' => 'nullable|email|max:254',
            'coverage.*.whatsapp' => ['nullable', 'regex:/^\+[1-9][0-9]{7,14}$/D'],
            'coverage.*.channel' => ['nullable', Rule::in(array_keys(EventCoverage::CHANNELS))],
        ])['coverage'];

        $roles = (new EventCoverage(['type' => $data['type']]))->roles();
        $contacts = [];
        foreach ($roles as $role) {
            $contact = array_filter($data[$role] ?? []);
            if (!empty($contact['email'])) {
                $contact['email'] = strtolower($contact['email']);
            }
            $contacts[$role] = $contact;
        }
        if ($data['type'] === 'both' && !empty($data['same'])) {
            $contacts['video'] = $contacts['photo'];
        }
        if ($data['type'] === 'both' && !empty($contacts['photo']['email'])
            && $contacts['photo']['email'] === ($contacts['video']['email'] ?? null)
            && $contacts['photo'] !== $contacts['video']) {
            throw ValidationException::withMessages(['coverage.same' => 'Marque “A mesma pessoa faz foto e vídeo” para usar um único contato e canal.']);
        }

        return [
            'type' => $data['type'], 'contacts' => $contacts,
            'starts_at' => $roles ? ($data['starts_at'] ?? null) : null,
            'ends_at' => $roles ? ($data['ends_at'] ?? null) : null,
            'deadline' => $roles ? ($data['deadline'] ?? null) : null,
        ];
    }

    private function validated(Request $request): array
    {
        $request->merge(['all_day' => $request->boolean('all_day')]);
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'event_date' => 'required|date_format:Y-m-d',
            'all_day' => 'boolean',
            'starts_at' => 'exclude_if:all_day,true|required|date_format:H:i',
            'ends_at' => ['exclude_if:all_day,true', 'required', 'date_format:H:i', Rule::when(
                !$request->filled('ends_on') || $request->input('event_date') === $request->input('ends_on'),
                ['different:starts_at']
            )],
            'ends_on' => 'nullable|date_format:Y-m-d|after_or_equal:event_date',
            'address' => 'required|string|max:250',
            'quantity' => 'required|integer|min:1|max:1000000',
            'quantity_unit' => 'required|string|max:40',
            'mascot' => ['required', Rule::in(array_keys(ShakeEvent::MASCOT))],
            'status' => ['required', Rule::in(array_keys(ShakeEvent::STATUSES))],
            'responsible' => 'required|string|max:120',
            'notes' => 'required|string|max:4000',
        ], [], [
            'title' => 'nome do evento', 'event_date' => 'data', 'starts_at' => 'horário de início',
            'ends_at' => 'horário de término', 'address' => 'endereço', 'quantity' => 'quantidade',
            'quantity_unit' => 'unidade', 'mascot' => 'presença do Mr. Shake Burger',
            'responsible' => 'responsável', 'notes' => 'observações', 'ends_on' => 'data de término',
        ]);

        if ($validated['all_day']) {
            $validated['starts_at'] = '00:00';
            $validated['ends_at'] = null;
        }
        $validated['ends_on'] ??= null;

        return $validated;
    }
}
