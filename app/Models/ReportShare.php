<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL — a public share link for a generated report (no login required).
 * Holds a copy of the rendered HTML plus optional password and access window.
 */
class ReportShare extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'token',
        'workspace_id',
        'generated_report_id',
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
