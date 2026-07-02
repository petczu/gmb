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
</style>
