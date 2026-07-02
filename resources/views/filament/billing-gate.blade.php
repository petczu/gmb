@php
    $wsId = session('current_workspace_id');
    $ws = $wsId ? \App\Models\Workspace::find($wsId) : null;
    $gate = $ws ? app(\App\Services\Billing\SubscriptionGate::class) : null;
    $state = $ws ? $gate->state($ws) : 'off';
    // Pages reachable without an active plan: Billing (to subscribe), Company
    // (details before paying), the onboarding wizard and the user's own Profile.
    // NOTE: the panel lives at the ROOT path — no app/ prefix here.
    $onBilling = request()->is('billing*') || request()->is('company*') || request()->is('profile*') || request()->is('onboarding*');
    // A workspace pending GDPR deletion is locked everywhere except Company
    // (to cancel the request) and the user's own Profile.
    $pendingDeletion = $ws && $ws->isPendingDeletion();
    $onSettings = request()->is('company*') || request()->is('profile*');
    // First-run onboarding: guide a brand-new workspace through setup. The step
    // pages must stay reachable, so they're exempt from the overlay.
    $onboarding = $ws && $ws->isOnboarding();
    // The panel lives at the ROOT path — these must not be prefixed with app/
    // (the previous app/* check never matched, so the overlay covered the very
    // pages needed to finish onboarding). The wizard page is exempt too.
    $obExempt = request()->is('onboarding*') || request()->is('company*') || request()->is('billing*') || request()->is('profile*') || request()->is('locations*') || request()->is('connect-location*') || request()->is('connect/*');
    $obSteps = $onboarding ? app(\App\Services\Onboarding\OnboardingStatus::class)->steps($ws) : [];
    $obNext = null;
    foreach ($obSteps as $obStep) { if (! $obStep['done']) { $obNext = $obStep; break; } }

    // True when any full-screen overlay below is rendered. While it is, lift the
    // top bar above the overlay (so the workspace switcher + user menu stay
    // usable) and hide the sidebar-collapse arrows (navigation is blocked anyway).
    // During onboarding the wizard owns the plan step, so the paywall stays out
    // of the way until onboarding is complete.
    $overlayShown = ($pendingDeletion && ! $onSettings)
        || ($onboarding && ! $obExempt)
        || (! $onboarding && ! $onBilling && in_array($state, ['needs_plan', 'payment_problem'], true));
@endphp

@if ($overlayShown)
    <style>
        /* The overlay starts below the top bar, so the workspace switcher + user
           menu stay visible. Hide the sidebar-collapse arrows (nav is blocked)
           and keep the logo pinned in the top bar at every width (otherwise on
           narrow screens it moves into the covered sidebar). */
        .fi-topbar-close-collapse-sidebar-btn,
        .fi-topbar-open-collapse-sidebar-btn,
        .fi-topbar-close-sidebar-btn,
        .fi-topbar-open-sidebar-btn { display: none !important; }
        .fi-topbar .fi-topbar-start { display: flex !important; }
    </style>
@endif

@if ($pendingDeletion)
    @if (! $onSettings)
        <div style="position:fixed; top:4rem; inset-inline:0; bottom:0; z-index:60; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.92); backdrop-filter:blur(5px); padding:1rem;">
            <div style="max-width:460px; width:100%; background:#fff; border:1px solid rgb(254 202 202); border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.15); padding:2rem; text-align:center;">
                <div style="font-size:2rem;">🗑️</div>
                <h2 style="font-size:1.25rem; font-weight:700; margin:0.5rem 0;">{{ __('onboarding.deletion_title') }}</h2>
                <p style="color:rgb(107 114 128); font-size:0.9rem; margin-bottom:1.25rem;">
                    {!! __('onboarding.deletion_body', ['date' => $ws->deletionPurgeAt()?->translatedFormat('j. F Y')]) !!}
                </p>
                <a href="/company"
                   style="display:inline-block; background:rgb(16 185 129); color:#fff; font-weight:600; padding:0.65rem 1.4rem; border-radius:0.6rem; text-decoration:none;">
                    {{ __('onboarding.cancel_deletion') }}
                </a>
                <form method="POST" action="{{ route('filament.app.auth.logout') }}" style="margin-top:1rem;">
                    @csrf
                    <button type="submit" style="background:none; border:0; color:rgb(156 163 175); font-size:0.8rem; cursor:pointer; text-decoration:underline;">{{ __('onboarding.sign_out') }}</button>
                </form>
            </div>
        </div>
    @endif
@elseif ($onboarding && ! $obExempt)
    <div style="position:fixed; top:4rem; inset-inline:0; bottom:0; z-index:60; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.94); backdrop-filter:blur(5px); padding:1rem;">
        <div style="max-width:480px; width:100%; background:#fff; border:1px solid rgb(229 231 235); border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.15); padding:2rem;">
            <div style="text-align:center; margin-bottom:1.25rem;">
                <div style="font-size:2rem;">👋</div>
                <h2 style="font-size:1.25rem; font-weight:700; margin:0.4rem 0 0.2rem;">{{ __('onboarding.welcome_title') }}</h2>
                <p style="color:rgb(107 114 128); font-size:0.875rem; margin:0;">{{ __('onboarding.welcome_subtitle') }}</p>
            </div>

            @foreach ($obSteps as $obStep)
                <a href="{{ $obStep['url'] }}" style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.7rem 0.6rem; border-radius:0.6rem; text-decoration:none; color:inherit;">
                    @if ($obStep['done'])
                        <span style="flex:none; width:24px; height:24px; border-radius:999px; background:#16a34a; color:#fff; display:flex; align-items:center; justify-content:center; font-size:0.8rem;">✓</span>
                    @else
                        <span style="flex:none; width:24px; height:24px; border-radius:999px; border:2px solid rgb(209 213 219); display:flex; align-items:center; justify-content:center; font-size:0.7rem; color:rgb(156 163 175);">{{ $loop->iteration }}</span>
                    @endif
                    <span>
                        <span style="display:block; font-weight:600; font-size:0.9rem; {{ $obStep['done'] ? 'color:rgb(107 114 128); text-decoration:line-through;' : '' }}">{{ $obStep['label'] }}</span>
                        <span style="display:block; color:rgb(156 163 175); font-size:0.8rem;">{{ $obStep['hint'] }}</span>
                    </span>
                </a>
            @endforeach

            <a href="{{ $obNext['url'] ?? '/' }}"
               style="display:block; text-align:center; margin-top:1rem; background:#2d19ec; color:#fff; font-weight:600; padding:0.7rem 1.4rem; border-radius:0.6rem; text-decoration:none;">
                {{ $obNext ? __('onboarding.continue_step', ['label' => $obNext['label']]) : __('onboarding.enter_app') }}
            </a>
            <form method="POST" action="{{ route('filament.app.auth.logout') }}" style="margin-top:0.9rem; text-align:center;">
                @csrf
                <button type="submit" style="background:none; border:0; color:rgb(156 163 175); font-size:0.8rem; cursor:pointer; text-decoration:underline;">{{ __('onboarding.sign_out') }}</button>
            </form>
        </div>
    </div>
@elseif ($state === 'grace')
    @php($until = $gate->graceEndsAtFor($ws))
    <div style="position:fixed; top:0; left:0; right:0; z-index:60; background:#fffbeb; border-bottom:1px solid #fde68a; color:#92400e; padding:0.6rem 1rem; text-align:center; font-size:0.9rem;">
        {!! __('onboarding.grace_banner', ['date' => $until?->translatedFormat('j. F Y')]) !!}
        <a href="/billing" style="text-decoration:underline; font-weight:600;">{{ __('onboarding.update_your_billing') }}</a>.
    </div>
@elseif (in_array($state, ['needs_plan', 'payment_problem'], true) && ! $onBilling)
    <div style="position:fixed; top:4rem; inset-inline:0; bottom:0; z-index:60; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.9); backdrop-filter:blur(5px); padding:1rem;">
        <div style="max-width:440px; width:100%; background:#fff; border:1px solid rgb(229 231 235); border-radius:1rem; box-shadow:0 20px 60px rgba(0,0,0,0.15); padding:2rem; text-align:center;">
            <div style="font-size:2rem;">{{ $state === 'payment_problem' ? '💳' : '🚀' }}</div>
            <h2 style="font-size:1.25rem; font-weight:700; margin:0.5rem 0;">
                {{ $state === 'payment_problem' ? __('onboarding.payment_problem_title') : __('onboarding.needs_plan_title') }}
            </h2>
            <p style="color:rgb(107 114 128); font-size:0.9rem; margin-bottom:1.25rem;">
                @if ($state === 'payment_problem')
                    {{ __('onboarding.payment_problem_body') }}
                @else
                    {{ __('onboarding.needs_plan_body') }}
                @endif
            </p>
            <a href="/billing"
               style="display:inline-block; background:rgb(245 158 11); color:#fff; font-weight:600; padding:0.65rem 1.4rem; border-radius:0.6rem; text-decoration:none;">
                {{ $state === 'payment_problem' ? __('onboarding.update_billing') : __('onboarding.view_plans') }}
            </a>
            <form method="POST" action="{{ route('filament.app.auth.logout') }}" style="margin-top:1rem;">
                @csrf
                <button type="submit" style="background:none; border:0; color:rgb(156 163 175); font-size:0.8rem; cursor:pointer; text-decoration:underline;">{{ __('onboarding.sign_out') }}</button>
            </form>
        </div>
    </div>
@endif
