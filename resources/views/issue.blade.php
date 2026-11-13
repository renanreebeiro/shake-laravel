@extends('layout')
@section('title', $template->title)
@push('head')
    <script src="{{ asset('assets/pdf-lib.min.js') }}" defer></script>
@endpush
@section('content')
@php
    $labels = [
        'nome' => 'Nome do contratante', 'estado' => 'Estado civil', 'tipo_doc' => 'Tipo de documento',
        'cpf' => 'Número do CPF ou CNPJ', 'rg' => 'RG', 'endereco' => 'Endereço',
        'evento' => 'Tipo de evento', 'quantidade' => 'Quantidade de mini burgers',
        'data_evento' => 'Data do evento', 'hora' => 'Horário da entrega', 'local' => 'Local do evento',
        'total' => 'Valor total (R$)', 'total_extenso' => 'Valor total por extenso',
        'entrada' => 'Entrada (R$)', 'entrada_extenso' => 'Entrada por extenso',
        'data_entrada' => 'Data do pagamento da entrada', 'emissao' => 'Data de emissão',
        'termino' => 'Data de término',
        'num' => 'Número do recibo', 'recebi' => 'Nome do pagante',
        'cpf_pag' => 'CPF ou CNPJ do pagante', 'valor' => 'Valor recebido (R$)',
        'extenso' => 'Valor recebido por extenso', 'forma_pag' => 'Forma de pagamento',
        'referente' => 'Referente a', 'cidade' => 'Cidade de emissão',
        'nome_resp' => 'Nome do responsável', 'cpf_resp' => 'CPF ou CNPJ do responsável',
    ];
    $dateFields = ['data_evento', 'data_entrada', 'emissao', 'termino'];
    $moneyFields = array_values(array_filter($fields, fn ($field) =>
        in_array($field, ['valor', 'total', 'entrada'], true)
        || (str_starts_with($field, 'valor_') && !str_ends_with($field, '_extenso'))
    ));
    $choices = [
        'tipo_doc' => ['CPF', 'CNPJ'],
        'forma_pag' => ['PIX', 'Transferência', 'Dinheiro', 'Cartão'],
    ];
    $isReceipt = $template->slug === 'recibo-evento';
    if ($isReceipt) {
        $labels['evento'] = 'Nome do evento';
    }
    $deliveryText = [
        'entrega_objeto' => 'acompanhada de personagem/animador temático',
        'entrega_obrigacao' => 'e acompanhados de personagem/animador temático devidamente uniformizado',
    ];
@endphp
<h1 class="document-heading">@include('document-icon', ['slug' => $template->slug]) {{ $template->title }}</h1>
<p>Preencha os dados e confira o PDF antes de baixar. O preenchimento fica no seu navegador.</p>
@if($template->slug === 'contrato-evento')
    <p>Este modelo corresponde ao serviço com personagem.</p>
@endif
<div class="layout">
    <section>
        <h2>Dados do documento</h2>
        <form id="document-form" autocomplete="off">
            <noscript><p>Ative o JavaScript para gerar o PDF no navegador.</p></noscript>
            @foreach($fields as $field)
                @if($template->slug === 'contrato-evento' && isset($deliveryText[$field]))
                    <input type="hidden" data-field="{{ $field }}" value="{{ $deliveryText[$field] }}">
                    @continue
                @endif
                @php($isMoney = in_array($field, $moneyFields, true))
                <label for="field-{{ $field }}">{{ $labels[$field] ?? (ucfirst(str_replace('_', ' ', $field)) . ($isMoney ? ' (R$)' : '')) }}</label>
                @if(isset($choices[$field]))
                    <select id="field-{{ $field }}" data-field="{{ $field }}" required>
                        <option value="">Selecione</option>
                        @foreach($choices[$field] as $choice)
                            <option>{{ $choice }}</option>
                        @endforeach
                    </select>
                @elseif(in_array($field, $dateFields, true))
                    <input id="field-{{ $field }}" data-field="{{ $field }}" type="date" @if($isReceipt && $field === 'emissao') value="{{ now('America/Belem')->toDateString() }}" @endif min="1900-01-01" max="2200-12-31" required>
                @elseif($isMoney)
                    <div class="currency-input">
                        <span aria-hidden="true">R$</span>
                        <input id="field-{{ $field }}" data-field="{{ $field }}" inputmode="decimal" placeholder="1.250,50" maxlength="14" pattern="(?:[0-9]{1,9}|[0-9]{1,3}(?:\.[0-9]{3}){1,2})(?:,[0-9]{1,2})?" aria-describedby="hint-{{ $field }}" required>
                    </div>
                    <small id="hint-{{ $field }}">Use vírgula para os centavos.</small>
                @elseif($field === 'quantidade')
                    <input id="field-{{ $field }}" data-field="{{ $field }}" type="number" min="1" step="1" required>
                @else
                    <input id="field-{{ $field }}" data-field="{{ $field }}" maxlength="500" required>
                @endif
            @endforeach
            <button type="submit" disabled>Gerar prévia</button>
        </form>
    </section>
    <section>
        <h2>Prévia do PDF</h2>
        <p id="preview-message" role="status">A prévia aparece aqui depois de preencher o formulário.</p>
        <iframe id="pdf-preview" title="Prévia do PDF" hidden></iframe>
        <a id="download" download="shake-burger-{{ $template->slug }}.pdf" hidden>Baixar PDF</a>
    </section>
</div>

<script type="module">
    import { compose } from @json(asset('assets/pdf.js'));

    const template = {{ Illuminate\Support\Js::from($template->body) }};
    const title = {{ Illuminate\Support\Js::from($template->title) }};
    const form = document.getElementById('document-form');
    const message = document.getElementById('preview-message');
    const preview = document.getElementById('pdf-preview');
    const download = document.getElementById('download');
    const dateFields = @json($dateFields);
    const moneyFields = @json($moneyFields);
    const reais = new Intl.NumberFormat('pt-BR', {style: 'currency', currency: 'BRL'});
    let currentUrl, revision = 0;

    function clearPreview() {
        revision += 1;
        preview.hidden = download.hidden = true;
        preview.removeAttribute('src');
        download.removeAttribute('href');
        if (currentUrl) URL.revokeObjectURL(currentUrl);
        currentUrl = undefined;
    }

    form.addEventListener('input', event => {
        const field = event.target;
        if (field.matches('input[required]')) {
            field.setCustomValidity(field.value && !field.value.trim() ? 'Preencha este campo.' : '');
        }
        clearPreview();
        message.textContent = 'Gere a prévia para conferir os dados atualizados.';
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        clearPreview();
        const requestedRevision = revision;
        message.textContent = 'Preparando PDF…';
        try {
            const values = new Map(Array.from(form.querySelectorAll('[data-field]'), input => [input.dataset.field, input.value]));
            const body = template.replace(/(R\$[^\S\r\n]*)?\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/g, (_, prefix = '', key) => {
                const value = String(values.get(key) ?? '').trim();
                if (moneyFields.includes(key)) {
                    const amount = Number(value.replaceAll('.', '').replace(',', '.'));
                    return reais.format(amount);
                }
                if (dateFields.includes(key)) return prefix + value.split('-').reverse().join('/');
                return prefix + value;
            });
            const response = await fetch(@json(asset('assets/timbrado.jpg')));
            if (!response.ok) throw new Error('Timbrado indisponível');
            const bytes = await compose(title, body, await response.arrayBuffer());
            if (revision !== requestedRevision) return;
            currentUrl = URL.createObjectURL(new Blob([bytes], {type:'application/pdf'}));
            preview.src = download.href = currentUrl;
            preview.hidden = download.hidden = false;
            message.textContent = 'PDF pronto para conferir e baixar.';
        } catch {
            if (revision === requestedRevision) message.textContent = 'O PDF não pôde ser gerado. Tente novamente.';
        }
    });
    form.querySelector('button[type=submit]').disabled = false;
</script>
@endsection
