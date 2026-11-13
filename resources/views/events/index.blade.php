@extends('events.layout')
@section('title', 'Eventos')
@push('head')
    <style>
        .events-hero.events-dashboard-banner {
            background-color: #18100c;
            background-image: linear-gradient(90deg, #18100cf2 0%, #18100ca6 44%, #18100c36 78%), var(--banner-image);
            background-position: center, right center;
            background-repeat: no-repeat;
            background-size: cover, clamp(26rem, 66%, 52rem) auto;
            min-height: 10rem;
        }
        .events-dashboard-banner--finance { --banner-image: url('data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('assets/financeiro-fundo.jpg'))) }}'); }
        .events-dashboard-banner--schedule { --banner-image: url('data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('assets/eventos-fundo.jpg'))) }}'); }
        .events-dashboard-banner :is(h1, h2) {
            margin: 0;
            font-size: clamp(1.75rem, 4.5vw, 3.4rem);
            line-height: 1.06;
            letter-spacing: -.045em;
        }
        @media (max-width: 600px) {
            .events-hero.events-dashboard-banner {
                background-image: linear-gradient(90deg, #18100cf5, #18100c80), var(--banner-image);
            }
        }
        .events-finance-divider { margin: 1rem 0; border: 0; border-top: 1px solid var(--border); }
    </style>
@endpush
@section('content')
    @foreach($errors->all() as $error)
        <p class="notice" role="alert">{{ $error }}</p>
    @endforeach
    @php($canViewFinance = auth()->user()->hasAreaAccess('finance'))
    @if($canViewFinance)
        <section class="events-hero events-dashboard-banner events-dashboard-banner--finance" aria-labelledby="events-finance-title">
            <h2 id="events-finance-title">Relatório financeiro</h2>
            <a class="hero-link" href="{{ route('events.finance') }}">Ver relatórios</a>
        </section>
        <hr class="events-finance-divider">
    @endif

    <header class="events-hero events-dashboard-banner events-dashboard-banner--schedule">
        <div>
            <h1>Gestor de eventos</h1>
            <p>Agenda e planejamento da equipe Shake Burger</p>
        </div>
        <a class="hero-link" href="{{ route('events.create') }}">＋ Novo evento</a>
    </header>

    @if(auth()->user()->is_admin)
        @php($calendar = app(\App\Services\ShakeGoogleCalendar::class))
        @if($calendar->configured() && !$calendar->connected())
            <form method="post" action="{{ route('calendar.connect') }}">
                @csrf
                <button type="submit">Conectar Google Agenda</button>
            </form>
        @endif
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
