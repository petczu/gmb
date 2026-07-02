<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL model — global, super-admin-managed email copy. One row per template
 * key + locale, holding an editable markdown body and subject that the
 * EmailTemplateRenderer wraps in the brand layout at send time.
 */
class EmailTemplate extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['key', 'locale', 'subject', 'body'];
}
