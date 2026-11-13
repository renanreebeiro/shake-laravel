@extends('events.layout')
@section('title', $document->title)
@section('content')
    <a class="events-back" href="{{ $event->trashed() ? route('events.deleted.history', $event->id) : route('events.show', $event) }}">‹ Voltar ao evento</a>
    <section class="events-sheet">
        <h1>{{ $document->title }}</h1>
        <p>Gerado em <time datetime="{{ $document->generated_at->toIso8601String() }}">{{ $document->generated_at->timezone('America/Belem')->format('d/m/Y H:i') }}</time></p>
        <div class="events-notes">{{ $document->body }}</div>
    </section>
@endsection
