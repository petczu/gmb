<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CENTRAL — an email address we must not send to (bounced or complained).
 */
class EmailSuppression extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['email', 'reason', 'detail'];

    public static function isSuppressed(string $email): bool
    {
        return static::query()->where('email', mb_strtolower(trim($email)))->exists();
    }

    public static function suppress(string $email, string $reason, ?string $detail = null): void
    {
        static::query()->updateOrCreate(
            ['email' => mb_strtolower(trim($email))],
            ['reason' => $reason, 'detail' => $detail],
        );
    }
}
