@extends('events.layout')
@section('title', $event->title)
@section('content')
    <a class="events-back" href="{{ route('events.index', ['month' => $event->event_date->format('Y-m')]) }}">‹ Voltar ao calendário</a>
    <section class="events-sheet">
        <div class="events-detail-heading">
            <div>
                <span class="events-chip status-{{ $event->status }}">{{ \App\Models\ShakeEvent::STATUSES[$event->status] }}</span>
                <h1>{{ $event->title }}</h1>
            </div>
            <a class="hero-link" href="{{ route('events.edit', $event) }}">Editar evento</a>
        </div>

        <dl class="events-facts">
            <div><dt>Data e horário</dt><dd>{{ $event->event_date->format('d/m/Y') }} · {{ $event->schedule() }}</dd></div>
            <div>
                <dt>Endereço</dt>
                <dd>
                    @if($event->address !== '')
                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($event->address) }}" target="_blank" rel="noopener noreferrer">{{ $event->address }} ↗</a>
                    @else
                        A definir
                    @endif
                </dd>
            </div>
            <div><dt>Quantidade</dt><dd>{{ $event->quantity !== null ? number_format($event->quantity, 0, ',', '.').' '.$event->quantity_unit : 'A definir' }}</dd></div>
            <div><dt>Mr. Shake Burger</dt><dd><span class="events-mascot mascot-{{ $event->mascot }}">{{ \App\Models\ShakeEvent::MASCOT[$event->mascot] }}</span></dd></div>
            @if($event->responsible)
                <div><dt>Responsável</dt><dd>{{ $event->responsible }}</dd></div>
            @endif
        </dl>
        @if($event->notes)
            <h2>Observações para a equipe</h2>
            <p class="events-notes">{{ $event->notes }}</p>
        @endif
    </section>
    @include('events.coverage-details')
@endsection
