@php($coverage = $event->coverage)
<section class="events-sheet coverage-section">
    <h2>Cobertura audiovisual</h2>
    <p>{{ \App\Models\EventCoverage::TYPES[$coverage?->type ?? 'pending'] }}</p>
    @if($coverage)
        @if($coverage->starts_at || $coverage->ends_at)
            <p>Horário: {{ substr($coverage->starts_at ?? '', 0, 5) }} — {{ substr($coverage->ends_at ?? '', 0, 5) }}</p>
        @endif
        @if($coverage->deadline)
            <p>Prazo para envio: {{ $coverage->deadline->format('d/m/Y') }}</p>
        @endif
        @if($coverage->folderUrl())
            <a href="{{ $coverage->folderUrl() }}" target="_blank" rel="noopener noreferrer">Abrir pasta no Drive ↗</a>
        @endif
        @foreach($coverage->roles() as $role)
            @php($contact = $coverage->contacts[$role] ?? [])
            <h3>{{ \App\Models\EventCoverage::ROLES[$role] }}</h3>
            <p>{{ $contact['name'] ?? 'A definir' }} · {{ $contact['email'] ?? 'E-mail a definir' }} · {{ $contact['whatsapp'] ?? 'WhatsApp a definir' }} · {{ \App\Models\EventCoverage::CHANNELS[$contact['channel'] ?? ''] ?? 'Canal a definir' }}</p>
            @if($coverage->folderUrl($role))
                <a href="{{ $coverage->folderUrl($role) }}" target="_blank" rel="noopener noreferrer">{{ \App\Models\EventCoverage::FOLDERS[$role] }} ↗</a>
            @endif
        @endforeach
        @if($coverage->pending)
            <p role="status">Cobertura aguardando processamento.</p>
        @endif
        @if($coverage->failure_stage)
            <p class="notice" role="alert"><strong>{{ $coverage->failure_stage }}</strong> · {{ $coverage->failure_message }}</p>
        @endif
        @if($coverage->roles() || $coverage->failure_stage)
            <form method="post" action="{{ route('coverage.retry', $event) }}">
                @csrf
                @if($event->coverageDeliveries->contains(fn ($delivery) => in_array($delivery->status, ['sending', 'uncertain'], true)))
                    <label><input name="confirm_resend" value="1" type="checkbox"> Conferi o canal e confirmo a repetição do envio pendente</label>
                @endif
                <button type="submit">Tentar cobertura novamente</button>
            </form>
        @endif
        <h3>Histórico de envio</h3>
        <ul>
            @forelse($event->coverageDeliveries->sortByDesc('id') as $delivery)
                <li>{{ ($delivery->sent_at ?? $delivery->created_at)->timezone('America/Belem')->format('d/m/Y H:i') }} · {{ $delivery->professional }} · {{ \App\Models\EventCoverage::CHANNELS[$delivery->channel] }} · {{ \App\Models\EventCoverageDelivery::STATUSES[$delivery->status] }}</li>
            @empty
                <li>Nenhum envio registrado.</li>
            @endforelse
        </ul>
    @endif
</section>
