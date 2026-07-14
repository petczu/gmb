{{-- Sits right after the page heading text (PAGE_HEADER_HEADING_AFTER). The
     heading is a block element, so it is switched to inline-block here to
     keep the star on the same line. --}}
<span style="display:inline;">
<style>.fi-header-heading { display:inline-block; vertical-align:middle; }</style>
<button
    type="button"
    wire:click="toggle"
    title="{{ $starred ? __('nav.favorite_remove') : __('nav.favorite_add') }}"
    style="display:inline-flex; align-items:center; justify-content:center; width:1.9rem; height:1.9rem; margin-left:.4rem; vertical-align:middle; border:0; border-radius:.5rem; background:transparent; cursor:pointer; color:{{ $starred ? '#f59e0b' : '#9ca3af' }};"
    onmouseover="this.style.background='rgb(0 0 0 / .05)'"
    onmouseout="this.style.background='transparent'"
>
    @if ($starred)
        <svg style="width:1.25rem; height:1.25rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z" clip-rule="evenodd"/></svg>
    @else
        <svg style="width:1.25rem; height:1.25rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
    @endif
</button>
</span>
