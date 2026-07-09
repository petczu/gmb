<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL model — admin watchlist of Google places whose snapshots are
 * collected even before any workspace tracks them, so a client adding such
 * a competitor later starts with history instead of "collecting…".
 */
class TrackedPlace extends Model
{
    /** Central table — pinned so tenancy never swaps the connection. */
    protected $connection = 'mysql';

    protected $fillable = ['place_id', 'name', 'address'];
}
