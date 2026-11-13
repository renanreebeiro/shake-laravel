@extends('events.layout')
@section('title', 'Google Drive da cobertura')
@section('content')
    <a class="events-back" href="{{ route('events.index') }}">‹ Voltar a Eventos</a>
    <section class="events-sheet">
        <h1>Google Drive da cobertura</h1>
        @foreach($errors->all() as $error)
            <p class="notice" role="alert">{{ $error }}</p>
        @endforeach
        @if($configured)
            <form method="post" action="{{ route('coverage.drive.connect') }}">
                @csrf
                <button type="submit">{{ $connection ? 'Reconectar Google Drive' : 'Conectar Google Drive' }}</button>
            </form>
        @else
            <p>Configure o cliente OAuth do Drive na hospedagem para liberar a conexão.</p>
        @endif

        @if($connection)
            <form method="post" action="{{ route('coverage.drive.folder') }}">
                @csrf
                <label for="folder">Link ou ID da pasta principal</label>
                <input id="folder" name="folder" required maxlength="500" value="{{ $connection->parent_id }}">
                <p>Escolha uma pasta privada de sua propriedade no Meu Drive.</p>
                @if($connection->parent_name)
                    <p>Pasta atual: <strong>{{ $connection->parent_name }}</strong></p>
                @endif
                <button type="submit">Salvar pasta principal</button>
            </form>
        @endif
    </section>
@endsection
