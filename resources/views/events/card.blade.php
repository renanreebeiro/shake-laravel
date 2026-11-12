<article class="events-card">
    <time datetime="{{ $event->event_date->toDateString() }}">{{ $event->event_date->format('d/m/Y') }} · {{ $event->schedule() }}</time>
    <h3><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a></h3>
    <p>{{ $event->address }}</p>
    @if($event->quantity !== null)
        <p>{{ number_format($event->quantity, 0, ',', '.') }} {{ $event->quantity_unit }}</p>
    @endif
    <span class="events-mascot mascot-{{ $event->mascot }}">Mr. Shake · {{ \App\Models\ShakeEvent::MASCOT[$event->mascot] }}</span>
    @if($view === 'kanban')
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
