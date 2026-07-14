{{-- Shown at the top of the reviews table when opened from a "new reviews"
     digest email (?reviews=1,2,3): the list is narrowed to just those. --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; padding:.7rem 1rem; background:rgb(45 25 236 / .06); border-bottom:1px solid rgb(0 0 0 / .06);">
    <span style="display:inline-flex; align-items:center; gap:.5rem; font-size:.88rem; color:#374151;">
        <svg style="width:1.05rem; height:1.05rem; color:#2d19ec; flex:none;" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
        {{ trans_choice('resources/reviews.from_email', $count, ['count' => $count]) }}
    </span>
    <x-filament::link tag="button" wire:click="clearEmailFilter" icon="heroicon-o-x-mark" size="sm">
        {{ __('resources/reviews.from_email_clear') }}
    </x-filament::link>
</div>
