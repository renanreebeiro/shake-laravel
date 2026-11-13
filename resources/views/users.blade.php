@extends('layout')
@section('title', 'Usuários')
@section('content')
<h1>Usuários</h1>
<p>Crie um acesso individual para cada pessoa da equipe.</p>
@if($errors->has('areas') || $errors->has('areas.*'))
    <p class="notice" role="alert">Selecione apenas as áreas disponíveis e salve novamente.</p>
@endif
<div class="layout">
    <section>
        <h2>Nova conta da equipe</h2>
        <form method="post" action="{{ route('users.store') }}">
            @csrf
            <label for="name">Nome</label>
            <input id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
            @error('name') <small>{{ $message }}</small> @enderror

            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="254" autocomplete="off" required>
            @error('email') <small>{{ $message }}</small> @enderror

            <label for="password">Senha inicial</label>
            <input id="password" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required>
            @error('password') <small>{{ $message }}</small> @enderror

            <label for="password_confirmation">Confirme a senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required>
            <button>Criar conta</button>
        </form>
    </section>
    <section>
        <h2>Contas cadastradas</h2>
        <ul class="team-accounts">
            @foreach($users as $user)
                <li>
                    <strong>{{ $user->name }}</strong> · {{ $user->is_admin ? 'Administração' : 'Equipe' }}<br>{{ $user->email }}
                    @if(!$user->is_admin)
                        <form method="post" action="{{ route('users.access', $user) }}">
                            @csrf
                            @method('PATCH')
                            <fieldset class="area-access">
                                <legend>Áreas liberadas para {{ $user->name }}</legend>
                                @foreach(\App\Models\User::AREAS as $area => $label)
                                    <label>
                                        <input type="checkbox" role="switch" name="areas[]" value="{{ $area }}" @checked($user->hasAreaAccess($area))>
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </fieldset>
                            <button type="submit">Salvar acessos</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
        @if($users->hasPages())
            <div aria-label="Páginas de usuários">
                @if($users->previousPageUrl())
                    <a href="{{ $users->previousPageUrl() }}" rel="prev">Anterior</a>
                @endif
                @if($users->nextPageUrl())
                    <a href="{{ $users->nextPageUrl() }}" rel="next">Próxima</a>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
