<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Documentos') | Shake Burger</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}?v={{ filemtime(public_path('assets/style.css')) }}">
    @stack('head')
</head>
<body>
<header class="site-header">
    <div class="topbar">
        <a class="brand" href="{{ url('/') }}" aria-label="Shake Burger — início">
            <span class="brand-logo"><img src="{{ asset('assets/logo-shake-burger.png') }}" alt=""></span>
        </a>
        @auth
            <nav aria-label="Navegação">
                @if(auth()->user()->hasAreaAccess('documents'))
                    <a href="{{ url('/') }}" @if(!request()->routeIs('templates.*', 'events.*', 'users.*')) aria-current="page" @endif>Documentos</a>
                @endif
                @if(auth()->user()->hasAreaAccess('events'))
                    <a href="{{ route('events.index') }}" @if(request()->routeIs('events.*')) aria-current="page" @endif>Eventos</a>
                @endif
                @if(auth()->user()->hasAreaAccess('templates'))
                    <a href="{{ route('templates.index') }}" @if(request()->routeIs('templates.*')) aria-current="page" @endif>Modelos</a>
                @endif
                @if(auth()->user()->is_admin)
                    <a href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) aria-current="page" @endif>Usuários</a>
                @endif
            </nav>
            <form action="{{ url('/sair') }}" method="post" class="logout">@csrf<button>Sair <span aria-hidden="true">↗</span></button></form>
        @else
            <span class="topbar-label">ACESSO DA EQUIPE</span>
        @endauth
    </div>
</header>
<main>
    @if(session('message')) <p class="notice" role="status">{{ session('message') }}</p> @endif
    @yield('content')
</main>
<footer class="site-footer"><span>SHAKE BURGER</span><span>@yield('footer-label', 'CENTRAL DE DOCUMENTOS')</span></footer>
</body>
</html>
