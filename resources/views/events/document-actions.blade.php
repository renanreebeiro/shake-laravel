<p class="event-document-actions">
    <a @if($asButtons ?? false) class="hero-link" @endif href="{{ route('events.documents.create', [$event, 'contrato']) }}">Criar contrato</a>
    <a @if($asButtons ?? false) class="hero-link" @endif href="{{ route('events.show', $event).'#recebimentos' }}">Criar recibo</a>
</p>
