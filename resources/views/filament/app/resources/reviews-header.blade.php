{{-- Reviews table header: status tabs rendered INSIDE the table card (reusing
     the approvals tab strip), plus the optional deep-link banner when the
     digest email filtered the list to specific reviews. --}}
<div class="fi-ta-header-ctn" style="display:flex; flex-direction:column; gap:1rem;">
    @include('filament.app.resources.auto-reply-tabs', ['tabs' => $tabs, 'activeTab' => $activeTab])

    @if ($emailCount > 0)
        @include('filament.app.resources.reviews-email-banner', ['count' => $emailCount])
    @endif
</div>
