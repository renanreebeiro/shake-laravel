@extends('layout')
@section('content')
<section class="hero">
    <div class="hero-content">
        <p class="eyebrow"><span></span> FERRAMENTAS DA EQUIPE</p>
        <h1>Central de <strong>documentos.</strong></h1>
        <p class="hero-description">Gere contratos e recibos no timbrado oficial da Shake Burger.</p>
        <a class="hero-link" href="#documentos">Ver documentos <span aria-hidden="true">↓</span></a>
    </div>
    <div class="hero-art" aria-hidden="true">
        <div class="art-orbit"></div>
        <div class="art-sheet art-sheet-back"></div>
        <div class="art-sheet art-sheet-front">
            <span class="brand-logo"><img src="{{ asset('assets/logo-shake-burger.png') }}" alt=""></span>
            <div class="art-lines"><i></i><i></i><i></i><i></i></div>
            <b>PDF</b>
        </div>
    </div>
</section>

<div id="documentos" class="document-section">
    <div class="section-heading">
        <div>
            <p class="eyebrow"><span></span> EMISSÃO</p>
            <h2>Escolha um documento</h2>
        </div>
        <p>{{ $templates->count() }} {{ $templates->count() === 1 ? 'modelo disponível' : 'modelos disponíveis' }}</p>
    </div>
    <div class="document-grid">
        @forelse($templates as $template)
            <a class="document-card" href="{{ route('documents.show', ['template' => $template->slug]) }}">
                <h3 class="document-heading">@include('document-icon', ['slug' => $template->slug]) {{ $template->title }}</h3>
                <span class="card-action">Preencher documento <span aria-hidden="true">↗</span></span>
            </a>
        @empty
            <section><p>Nenhum modelo disponível.</p></section>
        @endforelse
    </div>
</div>
@endsection
