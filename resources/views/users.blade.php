@extends('layout')
@section('title', 'Usuários')
@section('content')
<h1>Usuários</h1>
<p>Crie um acesso individual para cada pessoa da equipe.</p>
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
        <ul>
            @foreach($users as $user)
                <li><strong>{{ $user->name }}</strong> · {{ $user->is_admin ? 'Administração' : 'Equipe' }}<br>{{ $user->email }}</li>
            @endforeach
        </ul>
        {{ $users->links('pagination::simple-default') }}
    </section>
</div>
@endsection
