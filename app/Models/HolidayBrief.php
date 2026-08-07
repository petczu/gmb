<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL model — one AI-written explainer for a holiday/marketing day,
 * shared across every workspace (written once, read by all tenants).
 * Keyed by a hash of (country, date, title, locale).
 */
class HolidayBrief extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['key_hash', 'country', 'date', 'title', 'locale', 'brief'];

    protected $casts = ['date' => 'date'];
}
