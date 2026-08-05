<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TENANT model — a colored label for organizing calendar posts. Shares the
 * sticky-note color palette. Assignment lives in posts.label_ids (JSON).
 *
 * @property string $name
 * @property string $color
 */
class PostLabel extends Model
{
    /** Reuse the sticky-note palette; keys stored, hex pairs are bg/accent. */
    public const COLORS = PostNote::COLORS;

    protected $fillable = ['name', 'color'];
}
