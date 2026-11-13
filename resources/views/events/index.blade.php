@extends('events.layout')
@section('title', 'Eventos')
@section('content')
    <header class="events-hero">
        <div>
            <h1>Gestor de eventos</h1>
            <p>Agenda e planejamento da equipe Shake Burger</p>
        </div>
        <a class="hero-link" href="{{ route('events.create') }}">＋ Novo evento</a>
    </header>

    @if(auth()->user()->is_admin && app(\App\Services\ShakeGoogleCalendar::class)->configured())
        <form method="post" action="{{ route('calendar.connect') }}">
            @csrf
            <button type="submit">{{ app(\App\Services\ShakeGoogleCalendar::class)->connected() ? 'Reconectar Google Agenda' : 'Conectar Google Agenda' }}</button>
        </form>
    @endif

    @if(auth()->user()->is_admin)
        <p><a href="{{ route('coverage.drive') }}">Configurar Google Drive da cobertura</a></p>
    @endif

    <div class="events-toolbar">
        <nav class="events-switch" aria-label="Visualização dos eventos">
            <a href="{{ route('events.index') }}" @if($view === 'calendar') aria-current="page" @endif>Calendário</a>
            <a href="{{ route('events.index', ['view' => 'kanban']) }}" @if($view === 'kanban') aria-current="page" @endif>Kanban</a>
        </nav>
        @if($view === 'calendar')
            <nav class="events-month" aria-label="Navegação do calendário">
                <a href="{{ route('events.index', ['month' => $month->subMonth()->format('Y-m')]) }}" aria-label="Mês anterior">‹</a>
                <strong>{{ $month->translatedFormat('F Y') }}</strong>
                <a href="{{ route('events.index', ['month' => $month->addMonth()->format('Y-m')]) }}" aria-label="Próximo mês">›</a>
                <a href="{{ route('events.index') }}">Hoje</a>
            </nav>
        @endif
    </div>

    @if($view === 'calendar')
        <div class="events-calendar" aria-label="Calendário mensal">
            @foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $weekday)
                <div class="events-weekday">{{ $weekday }}</div>
            @endforeach
            @foreach($days as $day)
                <div class="events-day @if(!$day->isSameMonth($month)) events-outside @endif @if($day->isSameDay($today)) events-today @endif">
                    <div class="events-day-heading">
                        <time datetime="{{ $day->toDateString() }}">{{ $day->day }}</time>
                        @if($day->isSameMonth($month))
                            <a href="{{ route('events.create', ['date' => $day->toDateString()]) }}" aria-label="Novo evento em {{ $day->format('d/m/Y') }}">+</a>
                        @endif
                    </div>
                    @foreach($byDate->get($day->toDateString(), []) as $event)
                        <a class="events-chip status-{{ $event->status }}" href="{{ route('events.show', $event) }}">
                            <span>{{ $event->startLabel() }}</span> {{ $event->title }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div class="events-agenda">
            @foreach($byDate as $date => $dayEvents)
                <h2>{{ $dayEvents->first()->event_date->locale('pt_BR')->translatedFormat('d/m · l') }}</h2>
                @foreach($dayEvents as $event)
                    @include('events.card')
                @endforeach
            @endforeach
        </div>
        @if($monthEvents->isEmpty())
            <p class="events-empty">Nenhum evento cadastrado neste mês.</p>
        @endif
    @else
        <div class="events-board">
            @foreach(\App\Models\ShakeEvent::STATUSES as $status => $label)
                @php($items = $columns->get($status, collect()))
                <section class="events-column">
                    <h2><span class="events-dot status-{{ $status }}"></span>{{ $label }} <span class="events-count">{{ $items->count() }}</span></h2>
                    @forelse($items as $event)
                        @include('events.card')
                    @empty
                        <p class="events-empty">Nenhum evento</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    @endif
@endsection
