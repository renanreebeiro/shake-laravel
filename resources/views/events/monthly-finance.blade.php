@extends('events.layout')
@section('title', 'Relatório financeiro mensal')
@section('content')
    <a href="{{ route('events.index') }}">‹ Voltar a Eventos</a>
    <h1>Relatório financeiro mensal</h1>
    <form method="get" action="{{ route('events.finance') }}">
        <label for="report-month">Mês</label>
        <input id="report-month" type="month" name="month" value="{{ $month }}" required>
        <button type="submit">Consultar</button>
    </form>
    <section class="coverage-section">
        <h2>Eventos do mês · {{ \App\Models\EventMoney::format($events->sum('document_total')) }}</h2>
        <ul>
            @forelse($events as $event)
                <li>{{ $event->event_date->format('d/m/Y') }} · <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a> · {{ $event->document_total === null ? 'Valor a definir' : \App\Models\EventMoney::format($event->document_total) }}</li>
            @empty
                <li>Nenhum evento no mês.</li>
            @endforelse
        </ul>
    </section>
    <section class="coverage-section">
        <h2>Recebimentos do mês · {{ \App\Models\EventMoney::format($payments->sum('amount')) }}</h2>
        <ul>
            @forelse($payments as $payment)
                <li>{{ $payment->received_on->format('d/m/Y') }} · {{ $payment->event->title }} · {{ \App\Models\EventMoney::format($payment->amount) }} · {{ $payment->method }}</li>
            @empty
                <li>Nenhum recebimento no mês.</li>
            @endforelse
        </ul>
    </section>
@endsection
