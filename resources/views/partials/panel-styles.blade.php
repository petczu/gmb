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
    /* Inline light-theme grays inside the cards, brightened for dark. */
    .dark .panel-card [style*="color:rgb(55 65 81)"],
    .dark .panel-card [style*="color:rgb(75 85 99)"] { color: #d4d4d8 !important; }
    .dark .panel-card [style*="color:rgb(107 114 128)"] { color: #a1a1aa !important; }
    .dark .panel-card [style*="color:#2d19ec"] { color: #a5b4fc !important; }
    /* Positive/discount notes: dark green reads fine on white, not on dark. */
    .good-note { color: rgb(22 101 52); }
    .dark .good-note { color: #4ade80; }
    .dark .billing-edit-link { color: #a5b4fc !important; }
    /* "Trial · N days left" pill on the current plan card. */
    .trial-pill { display: inline-block; vertical-align: middle; margin-inline-start: .4rem; background: #eef2ff; color: #2d19ec; font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px; letter-spacing: .02em; }
    .dark .trial-pill { background: rgba(99,102,241,.18); color: #a5b4fc; }
</style>
