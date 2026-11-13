<fieldset class="coverage-section">
    <legend>Dados para documentos</legend>
    <label for="document_total">Valor total do evento (R$)</label>
    <input id="document_total" name="document_total" inputmode="decimal" placeholder="1.250,50"
        value="{{ old('document_total', $event->document_total === null ? '' : number_format($event->document_total / 100, 2, ',', '.')) }}">
    <label for="payment_due_on">Prazo de quitação (opcional)</label>
    <input id="payment_due_on" name="payment_due_on" type="date" value="{{ old('payment_due_on', $event->payment_due_on?->toDateString()) }}">
    <p>Se ficar em branco, o prazo será 10 dias antes do evento.</p>
    @foreach(\App\Models\ShakeEvent::DOCUMENT_FIELDS as $key => $label)
        <label for="document-{{ $key }}">{{ $label }}</label>
        @if($key === 'tipo_doc')
            <select id="document-{{ $key }}" name="document_data[{{ $key }}]">
                <option value="">Selecione</option>
                @foreach(['CPF', 'CNPJ'] as $type)
                    <option @selected(old('document_data.'.$key, $event->document_data[$key] ?? '') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        @else
            <input id="document-{{ $key }}" name="document_data[{{ $key }}]" maxlength="250"
                value="{{ old('document_data.'.$key, $event->document_data[$key] ?? '') }}">
        @endif
    @endforeach
</fieldset>
