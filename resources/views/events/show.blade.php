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
                    {{ $event->address ?: 'A definir' }}
                    @if($event->mapsLink())
                        <br><a href="{{ $event->mapsLink() }}" target="_blank" rel="noopener noreferrer">{{ $event->maps_url ? 'Abrir ponto exato no Google Maps' : 'Buscar endereço no Google Maps' }} ↗</a>
                    @endif
                </dd>
            </div>
            <div><dt>Quantidade</dt><dd>{{ $event->quantity !== null ? number_format($event->quantity, 0, ',', '.').' '.$event->quantity_unit : 'A definir' }}</dd></div>
            <div><dt>Mr. Shake Burger</dt><dd><span class="events-mascot mascot-{{ $event->mascot }}">{{ \App\Models\ShakeEvent::MASCOT[$event->mascot] }}</span></dd></div>
            @if($event->responsible)
                <div><dt>Responsável</dt><dd>{{ $event->responsible }}</dd></div>
            @endif
        </dl>
        @if($event->maps_embed_url)
            <h2>Mapa do evento</h2>
            <iframe src="{{ $event->maps_embed_url }}" title="Mapa do local do evento" loading="lazy" referrerpolicy="no-referrer" class="event-map" allowfullscreen></iframe>
        @endif
        @if($event->notes)
            <h2>Observações para a equipe</h2>
            <p class="events-notes">{{ $event->notes }}</p>
        @endif
    </section>
    @include('events.coverage-details')
@endsection
