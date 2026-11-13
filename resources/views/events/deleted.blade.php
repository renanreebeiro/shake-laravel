@extends('events.layout')
@section('title', 'Eventos excluídos')
@section('content')
    <a class="events-back" href="{{ route('events.index') }}">‹ Voltar a Eventos</a>
    <section class="events-sheet">
        <h1>Eventos excluídos</h1>
        <ul>
            @forelse($events as $event)
                <li><a href="{{ route('events.deleted.history', $event->id) }}">{{ $event->title }}</a> · Excluído em {{ $event->deleted_at->timezone('America/Belem')->format('d/m/Y H:i') }}</li>
            @empty
                <li>Nenhum evento excluído.</li>
            @endforelse
        </ul>
        @if($events->hasPages())
            <div>
                @if($events->previousPageUrl()) <a href="{{ $events->previousPageUrl() }}">Anterior</a> @endif
                @if($events->nextPageUrl()) <a href="{{ $events->nextPageUrl() }}">Próxima</a> @endif
            </div>
        @endif
    </section>
@endsection
