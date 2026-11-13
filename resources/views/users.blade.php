@extends('layout')
@section('title', 'Usuários')
@section('content')
<h1>Usuários</h1>
<p>Crie um acesso individual para cada pessoa da equipe.</p>
@if($errors->has('role'))
    <p class="notice" role="alert">Escolha um perfil válido e salve novamente.</p>
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
            <label for="role">Perfil</label>
            <select id="role" name="role" required>
                @foreach(\App\Models\User::teamRoles() as $role => $label)
                    <option value="{{ $role }}" @selected(old('role', 'member') === $role)>{{ $label }}</option>
                @endforeach
            </select>
            <button>Criar conta</button>
        </form>
    </section>
    <section>
        <h2>Contas cadastradas</h2>
        <ul class="team-accounts">
            @foreach($users as $user)
                <li>
                    <strong>{{ $user->name }}</strong> · {{ \App\Models\User::ROLES[$user->role()] }}<br>{{ $user->email }}
                    @unless(auth()->user()->is($user) || $user->role() === 'programmer')
                        <form method="post" action="{{ route('users.access', $user) }}">
                            @csrf
                            @method('PATCH')
                            <label for="role-{{ $user->id }}">Perfil de {{ $user->name }}</label>
                            <select id="role-{{ $user->id }}" name="role" required>
                                @foreach(\App\Models\User::teamRoles() as $role => $label)
                                    <option value="{{ $role }}" @selected($user->role() === $role)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit">Salvar perfil</button>
                        </form>
                    @endunless
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
