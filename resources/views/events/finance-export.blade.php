@use(App\Models\EventMoney)
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }} — {{ $period }}</title>
    <style>
        @page { size: A4 landscape; margin: 13mm; }
        body { margin: 1rem auto; max-width: 68rem; padding: 0 1rem; color: #221812; font: 14px/1.4 system-ui, sans-serif; overflow-wrap: anywhere; }
        h1 { margin: .25rem 0; }
        h2 { margin: 1.5rem 0 .5rem; font-size: 1.1rem; }
        .brand { font-weight: 800; color: #993b00; }
        .toolbar { margin-bottom: 1rem; }
        button { padding: .65rem 1rem; border: 0; border-radius: 2rem; background: #ff6d00; color: #221812; font: inherit; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .5rem; border: 1px solid #cbbcae; text-align: left; vertical-align: top; }
        thead th { background: #f5ece3; }
        .money { text-align: right; white-space: nowrap; }
        .table-wrap { overflow-x: auto; }
        @media print {
            body { margin: 0; padding: 0; max-width: none; font-size: 10pt; }
            .toolbar { display: none; }
            .table-wrap { overflow: visible; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
            h2 { break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Salvar como PDF</button>
        <p>Na janela de impressão, selecione “Salvar como PDF”.</p>
    </div>
    <div class="brand">Shake Burger</div>
    <h1>{{ $reportTitle }}</h1>
    <p>{{ $period }}</p>

    <div class="table-wrap">
        <table aria-label="Resumo financeiro">
            <thead>
                <tr>
                    <th scope="col">{{ isset($event) ? 'Valor total' : 'Valor dos eventos do mês' }}</th>
                    <th scope="col">{{ isset($event) ? 'Já recebido' : 'Recebido no mês' }}</th>
                    <th scope="col">{{ isset($event) ? 'Falta receber' : 'Falta receber dos eventos do mês' }}</th>
                    @isset($event)
                        <th scope="col">Prazo de quitação</th>
                    @endisset
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $total === null ? 'A definir' : EventMoney::format($total) }}</td>
                    <td>{{ EventMoney::format($received) }}</td>
                    <td>{{ $balance === null ? 'A definir' : EventMoney::format($balance) }}</td>
                    @isset($event)
                        <td>{{ $event->paymentDue()->format('d/m/Y') }}</td>
                    @endisset
                </tr>
            </tbody>
        </table>
    </div>

    @isset($ledger)
        <h2>Recebimentos e pendências por evento</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Evento</th><th scope="col">Data</th><th scope="col">Valor total</th><th scope="col">Já recebido</th><th scope="col">Falta receber</th></tr></thead>
                <tbody>
                    @forelse ($ledger as $entry)
                        <tr>
                            <th scope="row">{{ $entry->title }}</th>
                            <td>{{ $entry->event_date->format('d/m/Y') }}</td>
                            <td class="money">{{ $entry->document_total === null ? 'A definir' : EventMoney::format($entry->document_total) }}</td>
                            <td class="money">{{ EventMoney::format((int) $entry->payments_sum_amount) }}</td>
                            <td class="money">{{ $entry->document_total === null ? 'A definir' : EventMoney::format(max(0, $entry->document_total - $entry->payments_sum_amount)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nenhum evento ou recebimento no mês.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endisset

    <h2>{{ isset($event) ? 'Recebimentos do evento' : 'Recebimentos do mês' }}</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th scope="col">Data</th>@unless(isset($event))<th scope="col">Evento</th>@endunless<th scope="col">Forma de pagamento</th><th scope="col">Valor recebido</th></tr></thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->received_on->format('d/m/Y') }}</td>
                        @unless(isset($event))<td>{{ $payment->event?->title ?? 'Evento indisponível' }}</td>@endunless
                        <td>{{ $payment->method }}</td>
                        <td class="money">{{ EventMoney::format($payment->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ isset($event) ? 3 : 4 }}">Nenhum recebimento registrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
