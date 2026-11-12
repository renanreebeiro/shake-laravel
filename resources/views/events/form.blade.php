@extends('events.layout')
@section('title', $event->exists ? 'Editar evento' : 'Novo evento')
@section('content')
    <a class="events-back" href="{{ $event->exists ? route('events.show', $event) : route('events.index') }}">‹ Voltar</a>
    <section class="events-sheet">
        <h1>{{ $event->exists ? 'Editar evento' : 'Novo evento' }}</h1>
        @if($errors->any())
            <ul class="notice" role="alert">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <form method="post" action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}">
            @csrf
            @if($event->exists) @method('PATCH') @endif

            <label for="title">Nome do evento</label>
            <input id="title" name="title" maxlength="120" value="{{ old('title', $event->title) }}" required>

            <div class="events-fields events-three">
                <div>
                    <label for="event_date">Data</label>
                    <input id="event_date" name="event_date" type="date" value="{{ old('event_date', $event->event_date?->toDateString()) }}" required>
                </div>
                <div>
                    <label for="starts_at">Horário de início</label>
                    <input id="starts_at" name="starts_at" type="time" value="{{ old('starts_at', substr($event->starts_at ?? '', 0, 5)) }}" required>
                </div>
                <div>
                    <label for="ends_at">Horário de término</label>
                    <input id="ends_at" name="ends_at" type="time" value="{{ old('ends_at', substr($event->ends_at ?? '', 0, 5)) }}">
                </div>
            </div>

            <label for="address">Endereço</label>
            <input id="address" name="address" maxlength="250" placeholder="Rua, número, bairro e cidade" value="{{ old('address', $event->address) }}" required>

            <div class="events-fields">
                <div>
                    <label for="quantity">Quantidade</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="1000000" value="{{ old('quantity', $event->quantity) }}">
                </div>
                <div>
                    <label for="quantity_unit">Unidade</label>
                    <input id="quantity_unit" name="quantity_unit" maxlength="40" placeholder="Ex.: hambúrgueres, pessoas, kits" value="{{ old('quantity_unit', $event->quantity_unit) }}">
                </div>
                <div>
                    <label for="mascot">Presença do Mr. Shake Burger</label>
                    <select id="mascot" name="mascot" required>
                        @foreach(\App\Models\ShakeEvent::MASCOT as $key => $label)
                            <option value="{{ $key }}" @selected(old('mascot', $event->mascot) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status">Status do evento</label>
                    <select id="status" name="status" required>
                        @foreach(\App\Models\ShakeEvent::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $event->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label for="responsible">Responsável</label>
            <input id="responsible" name="responsible" maxlength="120" value="{{ old('responsible', $event->responsible) }}">

            <label for="notes">Observações para a equipe</label>
            <textarea id="notes" name="notes" maxlength="4000">{{ old('notes', $event->notes) }}</textarea>

            <button type="submit">Salvar evento</button>
        </form>
    </section>
@endsection
