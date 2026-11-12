@extends('layout')
@section('footer-label', 'GESTOR DE EVENTOS')
@push('head')
    <link rel="stylesheet" href="{{ asset('assets/events.css') }}?v={{ filemtime(public_path('assets/events.css')) }}">
@endpush
