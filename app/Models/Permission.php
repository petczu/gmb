<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Central-pinned Permission model (see [[Role]]).
 */
class Permission extends SpatiePermission
{
    protected $connection = 'mysql';
}
