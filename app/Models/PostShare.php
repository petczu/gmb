<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL — a public share link for a Google post (no login required), the
 * same mechanics as ReportShare: a snapshot of the rendered card plus an
 * optional password and access window.
 */
class PostShare extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'token',
        'workspace_id',
        'post_id',
        'title',
        'html',
        'password',
        'access_from',
        'access_until',
    ];

    protected $casts = [
        'access_from' => 'date',
        'access_until' => 'date',
    ];

    protected $hidden = ['password', 'html'];

    /** Whether today falls inside the (optional) access window. */
    public function withinWindow(): bool
    {
        $today = CarbonImmutable::now()->startOfDay();

        if ($this->access_from && $today->lt($this->access_from->startOfDay())) {
            return false;
        }

        if ($this->access_until && $today->gt($this->access_until->startOfDay())) {
            return false;
        }

        return true;
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }
}
