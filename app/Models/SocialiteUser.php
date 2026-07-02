<?php

declare(strict_types=1);

namespace App\Models;

use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser as BaseSocialiteUser;

/**
 * CENTRAL model — socialite_users live in the central DB (like users). Pin the
 * connection so provider lookups/links always hit the central DB even when a
 * tenant is initialized during the OAuth callback (auth happens pre-tenancy).
 */
class SocialiteUser extends BaseSocialiteUser
{
    protected $connection = 'mysql';
}
