@extends('events.layout')
@section('title', 'Relatório financeiro mensal')
@use(App\Models\EventMoney)
@push('head')
    @include('events.monthly-finance-style')
@endpush
@section('content')
    <a href="{{ route('events.index') }}">‹ Voltar a Eventos</a>
    <div class="finance-report">
        <div class="finance-overview">
            <section class="finance-summary">
                <h1>Relatório financeiro</h1>
                <a class="hero-link" href="{{ route('events.finance', ['month' => $month, 'format' => 'pdf']) }}" target="_blank" rel="noopener">Exportar mês em PDF</a>
                <p>Valor dos eventos do mês</p>
                <strong class="finance-total">{{ EventMoney::format($total) }}</strong>
                <p>{{ $events->count() }} {{ $events->count() === 1 ? 'evento' : 'eventos' }}</p>
                <form method="get" action="{{ route('events.finance') }}">
                    <input type="hidden" name="event_month" value="{{ $eventMonth }}">
                    <label for="report-month">Mês de referência</label>
                    <div class="finance-filter">
                        <input id="report-month" type="month" name="month" value="{{ $month }}" required>
                        <button type="submit">Consultar</button>
                    </div>
                </form>
            </section>
            <section class="finance-panel">
                <h2 id="finance-agenda-title">Eventos do mês</h2>
                <form method="get" action="{{ route('events.finance') }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <label for="event-month">Mês dos eventos</label>
                    <div class="finance-filter">
                        <input id="event-month" type="month" name="event_month" value="{{ $eventMonth }}" required>
                        <button type="submit">Consultar</button>
                    </div>
                </form>
                <div @if ($agenda->count() > 5) class="finance-agenda-scroll" tabindex="0" role="region" aria-labelledby="finance-agenda-title" @endif>
                    <ul class="finance-agenda">
                        @forelse ($agenda as $event)
                            <li>
                                @if ($event->status === 'completed')
                                    <span class="finance-paid" role="img" aria-label="Realizado">✓</span>
                                @endif
                                <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                                @if ($event->status !== 'completed')
                                    <small>{{ $event->event_date->format('d/m') }} · {{ $event->startLabel() }} · {{ $event->address ?: 'Local a definir' }}</small>
                                @endif
                            </li>
                        @empty
                            <li>Nenhum evento no mês.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>
        <section class="finance-panel finance-trend">
            <h2>Evolução dos recebimentos</h2>
            <p>Acumulado no mês</p>
            <div class="finance-plot" role="img" aria-label="Recebimentos acumulados. Total de {{ EventMoney::format($received) }}.">
                <div class="finance-axis" aria-hidden="true">
                    <span>{{ EventMoney::format($chartMax) }}</span>
                    <span>{{ EventMoney::format(intdiv($chartMax, 2)) }}</span>
                    <span>R$ 0</span>
                </div>
                <div>
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                        <defs><linearGradient id="finance-fill" x2="0%" y2="100%"><stop stop-color="#ff8124" stop-opacity=".58"/><stop offset="100%" stop-color="#ff6d00" stop-opacity=".025"/></linearGradient></defs>
                        <path d="M0 0 H100 M0 50 H100 M0 100 H100" fill="none" stroke="#ffffff30" vector-effect="non-scaling-stroke"/>
                        <path d="{{ $trend }} V100 Z" fill="url(#finance-fill)"/>
                        <path d="{{ $trend }}" class="finance-trend-line" fill="none" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
                    </svg>
                    <div class="finance-days" aria-hidden="true"><span>01</span><span>08</span><span>15</span><span>22</span><span>{{ $start->daysInMonth }}</span></div>
                </div>
            </div>
        </section>
        <div class="finance-metrics">
            <section class="finance-panel">
                <h2>Recebido no mês</h2>
                <strong class="finance-paid">{{ EventMoney::format($received) }}</strong>
                <p>{{ $payments->count() }} {{ $payments->count() === 1 ? 'pagamento' : 'pagamentos' }}</p>
            </section>
            <section class="finance-panel">
                <h2>Falta receber dos eventos do mês</h2>
                <strong class="finance-pending">{{ EventMoney::format($balance) }}</strong>
            </section>
        </div>
        <section class="finance-panel">
            <h2>Saldo dos eventos do mês</h2>
            @if ($total > 0)
                @php($paidShare = ($total - $balance) / $total * 100)
                <div class="finance-allocation">
                    <div class="finance-donut" style="--paid-share: {{ $paidShare }}%" aria-hidden="true">
                        <div><strong>{{ EventMoney::format($total) }}</strong><small>Valor total</small></div>
                    </div>
                    <dl>
                        <div><dt class="finance-paid">Já recebido</dt><dd>{{ EventMoney::format($total - $balance) }} · {{ number_format($paidShare, 1, ',', '.') }}%</dd></div>
                        <div><dt class="finance-pending">Falta receber</dt><dd>{{ EventMoney::format($balance) }} · {{ number_format(100 - $paidShare, 1, ',', '.') }}%</dd></div>
                    </dl>
                </div>
            @else
                <p>Nenhum valor de evento definido no mês.</p>
            @endif
        </section>
        <section class="finance-ledger">
            <h2>Recebimentos e pendências</h2>
            @if ($ledger->isEmpty())
                <p>Nenhum evento ou recebimento no mês.</p>
            @else
                <div class="finance-table-wrap" tabindex="0" role="region" aria-label="Tabela de recebimentos e pendências">
                    <table>
                        <thead><tr><th scope="col">Evento</th><th scope="col">Data</th><th scope="col">Valor total</th><th scope="col">Já recebido</th><th scope="col">Falta receber</th></tr></thead>
                        <tbody>
                            @foreach ($ledger as $event)
                                @php($entries = $paymentsByEvent->get($event->id, collect()))
                                <tr>
                                    <th scope="row">
                                        @if ($event->trashed())
                                            {{ $event->title }}
                                        @else
                                            <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                                        @endif
                                        @if ($entries->isNotEmpty())
                                            <details>
                                                <summary>Ver recebimentos</summary>
                                                <ul>
                                                    @foreach ($entries as $payment)
                                                        <li>{{ $payment->received_on->format('d/m/Y') }} · {{ $payment->method }} · {{ EventMoney::format($payment->amount) }}</li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif
                                    </th>
                                    <td>{{ $event->event_date->format('d/m/Y') }}</td>
                                    <td>{{ $event->document_total === null ? 'A definir' : EventMoney::format($event->document_total) }}</td>
                                    <td class="finance-paid">{{ EventMoney::format((int) $event->payments_sum_amount) }}</td>
                                    <td class="finance-pending">{{ $event->document_total === null ? 'A definir' : EventMoney::format(max(0, $event->document_total - $event->payments_sum_amount)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
