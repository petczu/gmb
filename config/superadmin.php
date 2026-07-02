<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Super-admin allowlist
    |--------------------------------------------------------------------------
    |
    | Comma-separated emails (in SUPER_ADMIN_EMAILS) that may access the /admin
    | panel. Kept in config (not the DB) so access can't be granted by a data
    | compromise. Matching is case-insensitive.
    |
    */
    'emails' => array_values(array_filter(array_map(
        fn (string $email): string => mb_strtolower(trim($email)),
        explode(',', (string) env('SUPER_ADMIN_EMAILS', '')),
    ))),
];
