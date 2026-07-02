<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A workspace's REST API key. Pinned to the CENTRAL connection so it can be
 * resolved before tenancy is initialized. Only the SHA-256 hash is stored; the
 * raw key (returned once at creation) is never persisted.
 *
 * @property string $workspace_id
 * @property string $name
 * @property string $prefix
 * @property string $key_hash
 * @property list<string> $abilities
 * @property ?Carbon $last_used_at
 * @property ?Carbon $expires_at
 */
class ApiKey extends Model
{
    protected $connection = 'mysql';

    protected $fillable = ['workspace_id', 'name', 'prefix', 'key_hash', 'abilities', 'expires_at'];

    protected $hidden = ['key_hash'];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Create a key for a workspace and return [model, rawKey]. The raw key is
     * shown to the user exactly once.
     *
     * @param  list<string>  $abilities
     * @return array{0: self, 1: string}
     */
    public static function generate(string $workspaceId, string $name, array $abilities, ?Carbon $expiresAt = null): array
    {
        $raw = 'ak_live_'.Str::random(40);

        $key = static::query()->create([
            'workspace_id' => $workspaceId,
            'name' => $name,
            'prefix' => substr($raw, 0, 13),
            'key_hash' => hash('sha256', $raw),
            'abilities' => array_values($abilities),
            'expires_at' => $expiresAt,
        ]);

        return [$key, $raw];
    }

    /** Resolve an active (non-expired) key by its raw value. */
    public static function findByRawKey(string $raw): ?self
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        $key = static::query()->where('key_hash', hash('sha256', $raw))->first();

        if ($key === null || $key->isExpired()) {
            return null;
        }

        return $key;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    public function markUsed(): void
    {
        // Avoid a write on every request — only touch once per minute.
        if ($this->last_used_at === null || $this->last_used_at->lt(now()->subMinute())) {
            $this->forceFill(['last_used_at' => now()])->saveQuietly();
        }
    }
}
