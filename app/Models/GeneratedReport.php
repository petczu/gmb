<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a saved snapshot (rendered HTML) of an AI-generated report.
 */
class GeneratedReport extends Model
{
    protected $fillable = [
        'title',
        'period_label',
        'language',
        'html',
        'generated_by',
        'generated_by_name',
    ];
}
