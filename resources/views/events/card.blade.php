<article class="events-card">
    <time datetime="{{ $event->event_date->toDateString() }}">{{ $event->event_date->format('d/m/Y') }} · {{ $event->schedule() }}</time>
    <h3><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a></h3>
    <p>{{ $event->address }}</p>
    @if($event->quantity !== null)
        <p>{{ number_format($event->quantity, 0, ',', '.') }} {{ $event->quantity_unit }}</p>
    @endif
    <span class="events-mascot mascot-{{ $event->mascot }}">Mr. Shake · {{ \App\Models\ShakeEvent::MASCOT[$event->mascot] }}</span>
    @if($view === 'kanban')
        @php($coverage = $event->coverage)
        <p><strong>Cobertura:</strong> {{ \App\Models\EventCoverage::TYPES[$coverage?->type ?? 'pending'] }}</p>
        @foreach($coverage?->roles() ?? [] as $role)
            @php($contact = $coverage->contacts[$role] ?? [])
            <p>{{ \App\Models\EventCoverage::ROLES[$role] }} · {{ $contact['name'] ?? 'A definir' }} · {{ $coverage->deliveryStatus($event, $role) }}</p>
        @endforeach
        @if($coverage?->folderUrl())
            <p><a href="{{ $coverage->folderUrl() }}" target="_blank" rel="noopener noreferrer">Abrir pasta no Drive ↗</a></p>
        @endif
        @if($coverage?->failure_stage)
            <p>{{ $coverage->failure_stage }} · precisa de atenção</p>
        @endif
        <form method="post" action="{{ route('events.status', $event) }}" class="events-move">
            @csrf
            @method('PATCH')
            <select name="status" aria-label="Mover {{ $event->title }} para">
                @foreach(\App\Models\ShakeEvent::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected($event->status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit">Mover</button>
        </form>
    @endif
</article>
