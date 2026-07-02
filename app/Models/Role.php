<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Roles live in the CENTRAL DB (users + workspace membership are central), so
 * pin the connection — otherwise queries are redirected to a tenant DB while
 * tenancy is initialized. Teams are enabled: team_id = workspace UUID.
 */
class Role extends SpatieRole
{
    protected $connection = 'mysql';
}
