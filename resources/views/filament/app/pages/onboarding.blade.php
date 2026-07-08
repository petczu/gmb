<x-filament-panels::page>
    {{-- The wizard is the only thing to do in a brand-new workspace: hide the
         sidebar (all nav redirects back here anyway) and center the content.
         The top bar stays for the workspace switcher + user menu (sign out).
         The default page header is replaced by a centered welcome block. --}}
    <style>
        .fi-sidebar,
        .fi-header,
        .fi-topbar-open-sidebar-btn,
        .fi-topbar-close-sidebar-btn,
        .fi-topbar-open-collapse-sidebar-btn,
        .fi-topbar-close-collapse-sidebar-btn { display: none !important; }
        .fi-main-ctn { margin-inline-start: 0 !important; }
        .fi-topbar .fi-topbar-start { display: flex !important; }
    </style>

    <div style="max-width: 66rem; margin: 0 auto; width: 100%;">
        <div style="text-align: center; margin: .5rem 0 1.5rem;">
            <div style="font-size: 2.25rem; line-height: 1;">👋</div>
            <h1 style="font-size: 1.55rem; font-weight: 700; margin: .55rem 0 .3rem;">{{ __('onboarding.welcome_title') }}</h1>
            <p style="color: #9ca3af; font-size: .95rem; margin: 0;">{{ __('onboarding.welcome_subtitle') }}</p>
        </div>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
