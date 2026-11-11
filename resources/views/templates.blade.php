@extends('layout')
@section('title', 'Modelos')
@section('content')
<h1>Modelos</h1>
<p>Use marcadores como <code>@{{ nome_do_cliente }}</code> no texto para criar campos no formulário.</p>
<div class="layout">
    <section>
        <h2>Catálogo</h2>
        <ul>
            @foreach($templates as $template)
                <li><a href="{{ route('templates.index', ['editar' => $template->id]) }}">{{ $template->title }}</a> · {{ $template->active ? 'Ativo' : 'Arquivado' }}</li>
            @endforeach
        </ul>
        <a href="{{ route('templates.index') }}">Novo modelo</a>
    </section>
    <section>
        <h2>{{ $selected ? 'Editar modelo' : 'Novo modelo' }}</h2>
        <form method="post" action="{{ route('templates.save', $selected ? ['template' => $selected->id] : []) }}">
            @csrf
            <label for="title">Título</label>
            <input id="title" name="title" maxlength="120" value="{{ old('title', $selected?->title) }}" required>
            @error('title') <small>{{ $message }}</small> @enderror
            <label for="body">Texto</label>
            <textarea id="body" name="body" rows="14" maxlength="20000" required>{{ old('body', $selected?->body) }}</textarea>
            @error('body') <small>{{ $message }}</small> @enderror
            <label><input type="checkbox" name="active" value="1" @checked(old('active', $selected?->active))> Disponível para a equipe</label>
            <button>Salvar modelo</button>
        </form>
    </section>
</div>
@endsection
