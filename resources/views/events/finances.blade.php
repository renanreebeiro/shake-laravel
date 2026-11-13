@php
    $received = $payments->sum('amount');
    $total = $event->document_total;
    $balance = max(0, ($total ?? 0) - $received);
@endphp
<section class="events-sheet coverage-section">
    <div class="events-detail-heading">
        <h2>Financeiro e documentos</h2>
        @unless($archived ?? false)
            @if(auth()->user()->hasAreaAccess('documents'))
                @include('events.document-actions', ['asButtons' => true])
            @endif
        @endunless
    </div>
    <dl class="events-facts">
        <div><dt>Valor total</dt><dd>{{ $total === null ? 'A definir' : \App\Models\EventMoney::format($total) }}</dd></div>
        <div><dt>Recebido</dt><dd>{{ \App\Models\EventMoney::format($received) }}</dd></div>
        <div><dt>Saldo a pagar</dt><dd>{{ $total === null ? 'A definir' : \App\Models\EventMoney::format($balance) }}</dd></div>
        <div><dt>Prazo de quitação</dt><dd>{{ $event->paymentDue()->format('d/m/Y') }}</dd></div>
    </dl>
    @if($total > 0)
        <label for="payment-progress">Recebido em relação ao total ({{ round($received * 100 / $total, 1) }}%)</label>
        <progress id="payment-progress" class="event-payment-progress" value="{{ $received }}" max="{{ $total }}">{{ round($received * 100 / $total) }}%</progress>
    @endif

    <h3 id="recebimentos">Recebimentos</h3>
    @unless($archived ?? false)
        @if($balance > 0)
            <form method="post" action="{{ route('events.payments.store', $event) }}">
                @csrf
                <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                @foreach($errors->all() as $error)
                    <p class="notice" role="alert">{{ $error }}</p>
                @endforeach
                <label for="payment-amount">Valor recebido (R$)</label>
                <input id="payment-amount" name="amount" inputmode="decimal" placeholder="300,00" value="{{ old('amount') }}" required>
                <label for="payment-date">Data do recebimento</label>
                <input id="payment-date" type="date" name="received_on" max="{{ now('America/Belem')->toDateString() }}" value="{{ old('received_on', now('America/Belem')->toDateString()) }}" required>
                <label for="payment-method">Forma de pagamento</label>
                <select id="payment-method" name="method" required>
                    <option value="">Selecione</option>
                    @foreach(\App\Models\EventPayment::METHODS as $method)
                        <option @selected(old('method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
                <button type="submit">Registrar recebimento</button>
            </form>
        @endif
    @endunless
    <ul>
        @forelse($payments as $payment)
            <li><time datetime="{{ $payment->received_on->toDateString() }}">{{ $payment->received_on->format('d/m/Y') }}</time>
                · {{ \App\Models\EventMoney::format($payment->amount) }} · {{ $payment->method }}
                @if($payment->document)
                    · <a href="{{ route('events.documents.show', [$event->id, $payment->document->id]) }}">Ver recibo</a>
                @elseif(!($archived ?? false) && auth()->user()->hasAreaAccess('documents'))
                    · <a href="{{ route('events.documents.create', [$event, 'recibo', 'payment' => $payment->id]) }}">Criar recibo</a>
                @endif
            </li>
        @empty
            <li>Nenhum recebimento registrado.</li>
        @endforelse
    </ul>

    <h3>Documentos gerados</h3>
    <ul>
        @forelse($documents as $document)
            <li><a href="{{ route('events.documents.show', [$event->id, $document->id]) }}">{{ $document->title }}{{ $document->receipt_number ? ' nº '.$document->receipt_number : '' }}</a>
                · <time datetime="{{ $document->generated_at->toIso8601String() }}">{{ $document->generated_at->timezone('America/Belem')->format('d/m/Y H:i') }}</time>
            </li>
        @empty
            <li>Nenhum documento gerado.</li>
        @endforelse
    </ul>
</section>
