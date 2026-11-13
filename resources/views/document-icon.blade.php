<svg class="document-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @if($slug === 'contrato-evento')
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5M14 2l6 6h-6zM8 9h3M8 13h2"/>
        <path d="m11 19 1-4 7-7 3 3-7 7z"/>
    @elseif(str_starts_with($slug, 'recibo'))
        <path d="M5 3l2 2 2-2 3 2 3-2 2 2 2-2v18l-2-2-2 2-3-2-3 2-2-2-2 2zM9 9h6m-6 4h6m-6 4h3"/>
    @else
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8m-8 4h6"/>
    @endif
</svg>
