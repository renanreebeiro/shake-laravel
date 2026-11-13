<?php

namespace App\Http\Controllers;

use App\Models\EventMoney;
use App\Models\EventPayment;
use App\Models\ShakeEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventPaymentController
{
    public function store(Request $request, ShakeEvent $event)
    {
        $data = $request->validate([
            'request_key' => 'required|uuid',
            'amount' => ['required', 'string', 'regex:/^'.EventMoney::PATTERN.'$/D'],
            'received_on' => 'required|date_format:Y-m-d|before_or_equal:'.now('America/Belem')->toDateString(),
            'method' => ['required', Rule::in(EventPayment::METHODS)],
        ]);

        DB::transaction(function () use ($request, $event, $data) {
            $event = ShakeEvent::lockForUpdate()->findOrFail($event->id);
            if ($event->payments()->where('request_key', $data['request_key'])->exists()) {
                return;
            }
            $amount = EventMoney::cents($data['amount']);
            if ($amount <= 0 || $amount > $event->document_total - $event->payments()->sum('amount')) {
                throw ValidationException::withMessages(['amount' => 'Informe um valor positivo dentro do saldo do evento.']);
            }
            $event->payments()->create(array_replace($data, ['amount' => $amount]));
            $event->activities()->create([
                'action' => 'payment_recorded', 'source' => 'central', 'occurred_at' => now(),
                'actor_name' => $request->user()->name, 'actor_email' => $request->user()->email,
            ]);
        });

        return back()->with('message', 'Recebimento registrado.');
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'event_month' => 'nullable|date_format:Y-m',
        ]);
        $currentMonth = now('America/Belem')->format('Y-m');
        $month = $filters['month'] ?? $currentMonth;
        $eventMonth = $filters['event_month'] ?? $currentMonth;
        $start = CarbonImmutable::createFromFormat('!Y-m', $month);
        $agendaStart = CarbonImmutable::createFromFormat('!Y-m', $eventMonth);
        $dates = [$start->toDateString(), $start->endOfMonth()->toDateString()];

        $events = ShakeEvent::whereBetween('event_date', $dates)->where('status', '!=', 'cancelled')
            ->withSum('payments', 'amount')->orderBy('event_date')->get();
        $agenda = ShakeEvent::whereBetween('event_date', [$agendaStart->toDateString(), $agendaStart->endOfMonth()->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', ['completed'])
            ->orderBy('event_date')->orderBy('starts_at')->orderBy('id')->get();
        $payments = EventPayment::with(['event' => fn ($query) => $query->withSum('payments', 'amount')])
            ->whereBetween('received_on', $dates)->latest('received_on')->get();
        $paymentsByEvent = $payments->groupBy('shake_event_id');
        $ledger = $events->concat($payments->pluck('event')->filter())->unique('id')->sortBy('event_date');

        $total = $events->sum('document_total');
        $balance = $events->sum(fn ($event) => max(0, $event->document_total - $event->payments_sum_amount));
        $received = $payments->sum('amount');
        $paymentsByDay = $payments->groupBy(fn ($payment) => $payment->received_on->day)->sortKeys();
        $chartMax = max(100, (int) ceil($received / 20000) * 20000);
        $trend = 'M0 100';
        $runningTotal = 0;
        foreach ($paymentsByDay as $day => $entries) {
            $runningTotal += $entries->sum('amount');
            $x = round(($day - 1) * 100 / ($start->daysInMonth - 1), 3);
            $y = round(100 - $runningTotal * 100 / $chartMax, 3);
            $trend .= " H{$x} V{$y}";
        }
        $trend .= ' H100';

        return response()->view('events.monthly-finance', compact(
            'month', 'eventMonth', 'agenda', 'events', 'payments', 'paymentsByEvent', 'ledger',
            'total', 'balance', 'received', 'chartMax', 'trend', 'start',
        ))->header('Cache-Control', 'private, no-store');
    }
}
