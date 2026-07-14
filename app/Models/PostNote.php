<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a private sticky note on the posts calendar. Notes are
 * internal planning aids (never published anywhere) pinned to a date, with a
 * Planable-style color and an optional free-form tag.
 *
 * @property Carbon $date
 * @property ?string $body
 * @property string $color
 * @property ?string $tag
 */
class PostNote extends Model
{
    /** The 9 base note colors; keys are stored, hex pairs are bg/accent. */
    public const COLORS = [
        'yellow' => ['#fef3c7', '#d97706'],
        'orange' => ['#ffedd5', '#ea580c'],
        'red' => ['#fee2e2', '#dc2626'],
        'pink' => ['#fce7f3', '#db2777'],
        'purple' => ['#f3e8ff', '#9333ea'],
        'blue' => ['#dbeafe', '#2563eb'],
        'teal' => ['#ccfbf1', '#0d9488'],
        'green' => ['#dcfce7', '#16a34a'],
        'gray' => ['#f4f4f5', '#52525b'],
    ];

    protected $fillable = ['date', 'body', 'color', 'tag', 'created_by', 'created_by_name'];

    protected $casts = ['date' => 'date'];
}
