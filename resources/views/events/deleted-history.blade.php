@extends('events.layout')
@section('title', 'Histórico do evento excluído')
@section('content')
    <a class="events-back" href="{{ route('events.deleted') }}">‹ Voltar aos eventos excluídos</a>
    <h1>{{ $event->title }}</h1>
    @if(auth()->user()->hasAreaAccess('finance'))
        @include('events.finances', ['archived' => true])
    @endif
    @include('events.activity')
@endsection
