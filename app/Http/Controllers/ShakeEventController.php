<?php

namespace App\Http\Controllers;

use App\Models\ShakeEvent;
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
            return view('events.index', ['view' => $view, 'columns' => $events->get()->groupBy('status')]);
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
        $event = ShakeEvent::create($this->validated($request));
        return redirect()->route('events.show', $event)->with('message', 'Evento cadastrado.');
    }

    public function show(ShakeEvent $event)
    {
        return view('events.show', compact('event'));
    }

    public function edit(ShakeEvent $event)
    {
        return view('events.form', compact('event'));
    }

    public function update(Request $request, ShakeEvent $event)
    {
        $event->update($this->validated($request));
        return redirect()->route('events.show', $event)->with('message', 'Evento atualizado.');
    }

    public function status(Request $request, ShakeEvent $event)
    {
        $event->update($request->validate(['status' => ['required', Rule::in(array_keys(ShakeEvent::STATUSES))]]));
        return redirect()->route('events.index', ['view' => 'kanban'])->with('message', 'Status atualizado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:120',
            'event_date' => 'required|date_format:Y-m-d',
            'starts_at' => 'required|date_format:H:i',
            'ends_at' => 'nullable|date_format:H:i|different:starts_at',
            'address' => 'required|string|max:250',
            'quantity' => 'nullable|required_with:quantity_unit|integer|min:1|max:1000000',
            'quantity_unit' => 'nullable|required_with:quantity|string|max:40',
            'mascot' => ['required', Rule::in(array_keys(ShakeEvent::MASCOT))],
            'status' => ['required', Rule::in(array_keys(ShakeEvent::STATUSES))],
            'responsible' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:4000',
        ], [], [
            'title' => 'nome do evento', 'event_date' => 'data', 'starts_at' => 'horário de início',
            'ends_at' => 'horário de término', 'address' => 'endereço', 'quantity' => 'quantidade',
            'quantity_unit' => 'unidade', 'mascot' => 'presença do Mr. Shake Burger',
            'responsible' => 'responsável', 'notes' => 'observações',
        ]);
    }
}
