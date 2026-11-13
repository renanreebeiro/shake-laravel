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

            <label><input id="all_day" name="all_day" type="checkbox" value="1" @checked(old('all_day', $event->all_day))> Dia inteiro</label>
            <div class="events-fields">
                <div>
                    <label for="event_date">Data</label>
                    <input id="event_date" name="event_date" type="date" value="{{ old('event_date', $event->event_date?->toDateString()) }}" required>
                </div>
                <div data-event-time>
                    <label for="starts_at">Horário de início</label>
                    <input id="starts_at" name="starts_at" type="time" value="{{ old('starts_at', substr($event->starts_at ?? '', 0, 5)) }}" required>
                </div>
                <div>
                    <label for="ends_on">Data de término</label>
                    <input id="ends_on" name="ends_on" type="date" value="{{ old('ends_on', $event->ends_on?->toDateString()) }}">
                    <small>Preencha se o evento terminar em outro dia.</small>
                </div>
                <div data-event-time>
                    <label for="ends_at">Horário de término</label>
                    <input id="ends_at" name="ends_at" type="time" value="{{ old('ends_at', substr($event->ends_at ?? '', 0, 5)) }}" required>
                </div>
            </div>

            <label for="address">Endereço</label>
            <input id="address" name="address" maxlength="250" placeholder="Rua, número, bairro e cidade" value="{{ old('address', $event->address) }}" required>

            <label for="maps_url">Link do ponto exato no Google Maps</label>
            <input id="maps_url" name="maps_url" type="url" maxlength="2048" placeholder="https://maps.app.goo.gl/…" value="{{ old('maps_url', $event->maps_url) }}" aria-describedby="maps-help">
            <p id="maps-help">No Maps, selecione o local ou o pino e use Compartilhar → Copiar link. <a href="https://www.google.com/maps" target="_blank" rel="noopener noreferrer">Abrir Google Maps ↗</a></p>

            <div class="events-fields">
                <div>
                    <label for="quantity">Quantidade</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="1000000" value="{{ old('quantity', $event->quantity) }}" required>
                </div>
                <div>
                    <label for="quantity_unit">Unidade</label>
                    <input id="quantity_unit" name="quantity_unit" maxlength="40" placeholder="Ex.: hambúrgueres, pessoas, kits" value="{{ old('quantity_unit', $event->quantity_unit) }}" required>
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
            <input id="responsible" name="responsible" maxlength="120" value="{{ old('responsible', $event->responsible) }}" required>

            <label for="notes">Observações para a equipe</label>
            <textarea id="notes" name="notes" maxlength="4000" required>{{ old('notes', $event->notes) }}</textarea>

            @include('events.coverage-form')
            <button type="submit">Salvar evento</button>
        </form>
    </section>
    <script>
        const allDay = document.getElementById('all_day');
        function updateEventTimes() {
            document.querySelectorAll('[data-event-time]').forEach(group => {
                group.hidden = allDay.checked;
                const input = group.querySelector('input');
                input.disabled = allDay.checked;
                input.required = !allDay.checked;
            });
        }
        allDay.addEventListener('change', updateEventTimes);
        updateEventTimes();
    </script>
@endsection
