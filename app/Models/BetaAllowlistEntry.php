<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL: an email that may sign up during the private beta without going
 * through the application queue. Managed in the super-admin panel.
 */
#[Fillable(['email'])]
class BetaAllowlistEntry extends Model
{
    protected $connection = 'mysql';

    protected $table = 'beta_allowlist';
}
