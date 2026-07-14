{{-- Status tabs rendered inside the table card's header slot (instead of the
     default floating block above it). State is the page's HasTabs $activeTab;
     clicking a tab sets it, which re-runs the table query via the trait. --}}
<x-filament::tabs class="fi-ta-header-tabs" contained="false">
    @foreach ($tabs as $key => $tab)
        <x-filament::tabs.item
            :active="$activeTab === $key"
            :badge="$tab->getBadge()"
            :badge-color="$tab->getBadgeColor()"
            wire:click="$set('activeTab', '{{ $key }}')"
            wire:key="tab-{{ $key }}"
        >
            {{ $tab->getLabel() }}
        </x-filament::tabs.item>
    @endforeach
</x-filament::tabs>
