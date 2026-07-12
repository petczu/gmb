{{-- Button colour overrides for the app panel. --}}
<style>
    .fi-bg-color-400 { background-color: #1800ff !important; }
    /* Hover set directly with !important, otherwise the base !important wins. */
    .fi-bg-color-400:hover { background-color: oklch(0.55 0.24 262.48) !important; }
    /* Text AND icon white inside the primary buttons. */
    .fi-bg-color-400, .fi-bg-color-400 svg, .fi-bg-color-400 .fi-icon { color: #ffffff !important; }
    .fi-text-color-950 { color: #ffffff !important; }
    .hover\:fi-text-color-800:hover { color: #ffffff !important; }

    /* Hide the social-login block on the 2FA challenge step: once email + password
       passed, the credentials #form is gone and #multiFactorChallengeForm is shown,
       so "Or log in via / Continue with Google" no longer makes sense there. */
    body:has(#multiFactorChallengeForm) [x-load-css*="filament-socialite"] { display: none !important; }

    /* Profile → Two-factor: the Enabled/Disabled badge lives in an after-label schema that
       grows and right-justifies (fi-inline fi-align-end), pushing it to the far edge.
       Pull it back so it sits right next to the provider title. */
    .mfa-form .fi-sc-actions-label-ctn > .fi-sc { flex-grow: 0 !important; justify-content: flex-start !important; }

    /* Lay each provider out as: [title + badge] … [buttons] on one row, description directly
       below — so the action buttons no longer open an empty gap between title and description. */
    .mfa-form .fi-sc-actions { display: grid !important; grid-template-columns: 1fr auto; align-items: center; column-gap: 0.75rem; row-gap: 0.25rem; }
    .mfa-form .fi-sc-actions > .fi-sc-actions-label-ctn { grid-column: 1; grid-row: 1; }
    .mfa-form .fi-sc-actions > .fi-ac { grid-column: 2; grid-row: 1; justify-self: end; }
    .mfa-form .fi-sc-actions > .fi-sc { grid-column: 1 / -1; grid-row: 2; }

    /* Subtle gray ring around the top-bar user avatar. */
    .fi-user-menu-trigger .fi-user-avatar { box-shadow: 0 0 0 1px rgb(0 0 0 / 0.12); }

    /* When a table is completely empty it shows the empty-state block, so the
       column header row is just noise — hide it. The empty-state is a sibling of
       the table wrapper, so scope the :has() to the outer .fi-ta-ctn root. */
    .fi-page:not(.fi-keep-column-headers) .fi-ta-ctn:has(.fi-ta-empty-state) .fi-ta-table thead { display: none; }

    /* Theme-aware info/warning boxes shared by page blades (light defaults,
       dark overrides — inline styles can't react to the theme). */
    .hint-box { border: 1px solid #e5e7eb; border-radius: .9rem; padding: 1rem 1.25rem; margin-bottom: 1.25rem; font-size: .85rem; color: #6b7280; }
    .dark .hint-box { border-color: rgba(255,255,255,.12); color: #a1a1aa; }
    .warn-box { border: 1px solid #fde68a; background: #fffbeb; color: #92400e; border-radius: .9rem; padding: 1.25rem 1.5rem; }
    .dark .warn-box { border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.08); color: #fcd34d; }
    .ok-box { border: 1px solid #bbf7d0; background: #f0fdf4; border-radius: .9rem; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
    .dark .ok-box { border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.08); }
    .ok-box-title { font-weight: 700; color: #166534; margin-bottom: .35rem; }
    .dark .ok-box-title { color: #86efac; }
    .ok-box-body { font-size: .85rem; color: #15803d; margin-bottom: .6rem; }
    .dark .ok-box-body { color: #4ade80; }
    .code-box { display: block; word-break: break-all; background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; padding: .6rem .75rem; font-size: .85rem; }
    .dark .code-box { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.14); color: #e4e4e7; }

    /* White content cards (plan cards, credit balance) + small form controls
       that page blades style inline for the light theme. */
    .panel-card { --card-border: rgb(229 231 235); background: #fff; }
    .dark .panel-card { --card-border: rgba(255,255,255,.14); background: #18181b; }
    .preset-btn { padding: .35rem .8rem; border: 1px solid rgb(209 213 219); border-radius: .5rem; background: #fff; cursor: pointer; font-size: .85rem; color: inherit; }
    .dark .preset-btn { background: transparent; border-color: rgba(255,255,255,.2); }
    .qty-input { width: 7rem; padding: .35rem .6rem; border: 1px solid rgb(209 213 219); border-radius: .5rem; font-size: .9rem; }
    .dark .qty-input { background: transparent; border-color: rgba(255,255,255,.2); color: inherit; }
    /* Small raw select/date controls in page blades (period pickers etc.). */
    .ctl-input { border: 1px solid #e5e7eb; border-radius: .6rem; padding: .4rem .7rem; font-size: .85rem; background: #fff; color: inherit; }
    .dark .ctl-input { background: #18181b; border-color: rgba(255,255,255,.2); }
    .dark .ctl-input option { background: #18181b; }
    .dark input.ctl-input::-webkit-calendar-picker-indicator { filter: invert(1); }
    /* Inline light-theme grays inside the cards, brightened for dark. */
    .dark .panel-card [style*="color:rgb(55 65 81)"],
    .dark .panel-card [style*="color:rgb(75 85 99)"] { color: #d4d4d8 !important; }
    .dark .panel-card [style*="color:rgb(107 114 128)"] { color: #a1a1aa !important; }
    .dark .panel-card [style*="color:#2d19ec"] { color: #a5b4fc !important; }
    /* Positive/discount notes: dark green reads fine on white, not on dark. */
    .good-note { color: rgb(22 101 52); }
    .dark .good-note { color: #4ade80; }
    .dark .billing-edit-link { color: #a5b4fc !important; }
    .muted-text { color: rgb(107 114 128); }
    .dark .muted-text { color: #a1a1aa; }
    /* Green "done" pill (e.g. a connected location). */
    .ok-pill { background: rgb(220 252 231); color: rgb(22 101 52); }
    .dark .ok-pill { background: rgba(34,197,94,.15); color: #4ade80; }
    /* Full-page busy overlay + its centered card. */
    .load-overlay { background: rgba(255,255,255,.55); }
    .dark .load-overlay { background: rgba(0,0,0,.55); }
    .load-card { background: #fff; color: rgb(55 65 81); box-shadow: 0 10px 40px rgba(0,0,0,.12); }
    .dark .load-card { background: #18181b; color: #e4e4e7; box-shadow: 0 10px 40px rgba(0,0,0,.6); }
    /* "Trial · N days left" pill on the current plan card. */
    .trial-pill { display: inline-block; vertical-align: middle; margin-inline-start: .4rem; background: #eef2ff; color: #2d19ec; font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px; letter-spacing: .02em; }
    .dark .trial-pill { background: rgba(99,102,241,.18); color: #a5b4fc; }

    /* Chart widgets: keep the metric/battle dropdown right NEXT TO the heading
       instead of the far right corner, where it collides with the arrange
       controls (drag/width/hide) in Customize mode. */
    .fi-wi-chart .fi-section-header { justify-content: flex-start; gap: .9rem; }
    /* Default is flex:1 (grow, basis 0) — shrink-wrap the heading instead. */
    .fi-wi-chart .fi-section-header .fi-section-header-text-ctn { flex: 0 1 auto; }
    .fi-wi-chart .fi-section-header-after-ctn { margin-inline-start: 0; margin-inline-end: auto; }

    /* Dashboard widgets that fetch external data (Zernio, Places): while a
       Livewire update is in flight the old content stays visible, so a
       wire:loading overlay signals that the block is refreshing. */
    .wi-load-wrap { position: relative; }
    .wi-load-overlay { position: absolute; inset: 0; z-index: 10; display: flex; align-items: center; justify-content: center; border-radius: .75rem; background: rgba(255,255,255,.65); backdrop-filter: blur(1px); }
    .dark .wi-load-overlay { background: rgba(24,24,27,.65); }
    .wi-load-spinner { width: 1.75rem; height: 1.75rem; border-radius: 999px; border: 3px solid rgb(0 0 0 / .12); border-top-color: #2d19ec; animation: wi-spin .7s linear infinite; }
    .dark .wi-load-spinner { border-color: rgb(255 255 255 / .15); border-top-color: #a5b4fc; }
    @keyframes wi-spin { to { transform: rotate(360deg); } }

    /* Multi-select fields (location filters etc.): a long selection blows the
       field up to many rows. Collapse to the first three badges plus a "+N"
       counter; focusing the field reveals the full list for editing. The extra
       badges stay visibility-hidden (not display:none) so the CSS counter that
       feeds "+N" still increments. */
    .fi-select-input-value-badges-ctn { counter-reset: extra-badges; position: relative; align-items: center; }
    .fi-select-input-value-badges-ctn > :nth-child(n + 4) {
        counter-increment: extra-badges;
        position: absolute;
        visibility: hidden;
        pointer-events: none;
    }
    .fi-select-input-value-badges-ctn:has(> :nth-child(4))::after {
        content: '+' counter(extra-badges);
        flex-shrink: 0;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1;
        padding: .25rem .5rem;
        border-radius: .375rem;
        background: rgb(0 0 0 / .06);
        color: rgb(107 114 128);
    }
    .dark .fi-select-input-value-badges-ctn:has(> :nth-child(4))::after { background: rgb(255 255 255 / .1); color: #a1a1aa; }
    .fi-fo-select:focus-within .fi-select-input-value-badges-ctn > :nth-child(n + 4) {
        position: static;
        visibility: visible;
        pointer-events: auto;
    }
    .fi-fo-select:focus-within .fi-select-input-value-badges-ctn::after { content: none; }
</style>
