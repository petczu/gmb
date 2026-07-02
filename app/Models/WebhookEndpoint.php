<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A workspace's outbound webhook subscription. Tenant-scoped — it lives in the
 * tenant DB and is fired from the tenant context, so no workspace_id column is
 * needed.
 *
 * @property string $url
 * @property string $secret
 * @property list<string> $events
 * @property bool $active
 */
class WebhookEndpoint extends Model
{
    protected $fillable = ['name', 'url', 'secret', 'events', 'active'];

    protected $hidden = ['secret'];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (WebhookEndpoint $endpoint): void {
            if (empty($endpoint->secret)) {
                $endpoint->secret = 'whsec_'.Str::random(40);
            }
        });
    }

    /**
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function subscribesTo(string $event): bool
    {
        return $this->active && in_array($event, $this->events ?? [], true);
    }
}
