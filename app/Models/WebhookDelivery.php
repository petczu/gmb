<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single attempt log for an outbound webhook. Tenant-scoped. `payload` holds
 * the exact JSON body that was signed and POSTed.
 *
 * @property int $webhook_endpoint_id
 * @property string $event
 * @property string $payload
 * @property string $status
 * @property ?int $response_status
 * @property int $attempts
 */
class WebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = ['webhook_endpoint_id', 'event', 'payload', 'status', 'response_status', 'response_body', 'attempts', 'last_attempt_at'];

    protected $casts = [
        'last_attempt_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<WebhookEndpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
