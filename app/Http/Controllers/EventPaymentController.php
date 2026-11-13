<?php

namespace App\Http\Controllers;

use App\Models\EventMoney;
use App\Models\EventPayment;
use App\Models\ShakeEvent;
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
        $month = $request->validate(['month' => 'nullable|date_format:Y-m'])['month'] ?? now('America/Belem')->format('Y-m');
        $start = \Carbon\CarbonImmutable::createFromFormat('!Y-m', $month);
        $dates = [$start->toDateString(), $start->endOfMonth()->toDateString()];
        $events = ShakeEvent::whereBetween('event_date', $dates)->where('status', '!=', 'cancelled')->orderBy('event_date')->get();
        $payments = EventPayment::with('event')->whereBetween('received_on', $dates)->latest('received_on')->get();

        return response()->view('events.monthly-finance', compact('month', 'events', 'payments'))->header('Cache-Control', 'private, no-store');
    }
}
