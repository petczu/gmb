<?php

declare(strict_types=1);

/**
 * Filament reads layout.direction to set <html dir>. Arabic is right-to-left,
 * so the whole panel (sidebar, tables, forms) mirrors automatically.
 */
return [
    'direction' => 'rtl',
];
