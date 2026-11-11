@extends('layout')
@section('title', 'Entrar')
@section('content')
<div class="login-layout">
<div class="hero login-hero">
    <div class="hero-content">
        <p class="eyebrow"><span></span> FERRAMENTAS DA EQUIPE</p>
        <h1>Central de <strong>documentos.</strong></h1>
        <p class="hero-description">Contratos e recibos da Shake Burger.</p>
    </div>
</div>
<section class="login">
    <h2>Entrar</h2>
    <p>Use sua conta da equipe.</p>
    <form method="post" action="{{ url('/entrar') }}">
        @csrf
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required>
        @error('email') <small>{{ $message }}</small> @enderror
        <label for="password">Senha</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password') <small>{{ $message }}</small> @enderror
        <button>Entrar</button>
    </form>
</section>
</div>
@endsection
