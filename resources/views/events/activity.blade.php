<section class="events-sheet coverage-section">
    <h2>Histórico do evento</h2>
    <ul>
        @forelse($activities as $activity)
            <li>
                <time datetime="{{ $activity->occurred_at->toIso8601String() }}">{{ $activity->occurred_at->timezone('America/Belem')->format('d/m/Y H:i') }}</time>
                · {{ \App\Models\EventActivity::ACTIONS[$activity->action] }}
                @if($activity->previous_status)
                    ({{ \App\Models\ShakeEvent::STATUSES[$activity->previous_status] }} → {{ \App\Models\ShakeEvent::STATUSES[$activity->new_status] }})
                @endif
                @if($activity->source === 'google')
                    · Google Agenda
                    @if($activity->actor_email)
                        · Criador no Google: {{ $activity->actor_email }}
                    @else
                        · Autor não informado pelo Google
                    @endif
                @else
                    · {{ $activity->actor_name }} ({{ $activity->actor_email }})
                @endif
            </li>
        @empty
            <li>Nenhuma atividade registrada.</li>
        @endforelse
    </ul>
    @if($activities->hasPages())
        <div>
            @if($activities->previousPageUrl()) <a href="{{ $activities->previousPageUrl() }}">Anterior</a> @endif
            @if($activities->nextPageUrl()) <a href="{{ $activities->nextPageUrl() }}">Próxima</a> @endif
        </div>
    @endif
</section>
