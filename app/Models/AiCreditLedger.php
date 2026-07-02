<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CENTRAL, append-only AI credit ledger entry. Pinned to the central connection.
 */
class AiCreditLedger extends Model
{
    protected $connection = 'mysql';

    protected $table = 'ai_credit_ledger';

    public const UPDATED_AT = null; // append-only; rows are never updated

    protected $fillable = [
        'workspace_id',
        'delta',
        'balance_after',
        'reason',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'ref_type',
        'ref_id',
        'meta',
    ];

    protected $casts = [
        'delta' => 'integer',
        'balance_after' => 'integer',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost_usd' => 'decimal:6',
        'meta' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
