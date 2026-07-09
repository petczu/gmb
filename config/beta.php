<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Private beta mode
    |--------------------------------------------------------------------------
    |
    | While enabled, new sign-ups only APPLY for access: no workspace is
    | provisioned and the app shows a "request received" screen until a
    | super admin activates the account (or the email is on the allowlist
    | managed at /admin). Set BETA_MODE=false to open self-service sign-up.
    |
    */
    'enabled' => (bool) env('BETA_MODE', true),
];
