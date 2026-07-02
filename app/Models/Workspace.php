<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Workspace extends BaseTenant implements TenantWithDatabase
{
    use Billable;
    use HasDatabase;
    use HasDomains;

    /** Cashier needs trial_ends_at as a date (stancl handles `data` separately). */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'deletion_requested_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
    ];

    /**
     * Columns stored as real DB columns (everything else lives in the `data` JSON column).
     * The stripe_* / pm_* / trial_ends_at columns back Cashier (Billable=Workspace).
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
            'deletion_requested_at',
            'deletion_requested_by',
            'onboarding_completed_at',
        ];
    }

    /** Whether the workspace opted into write tools over MCP (default off). */
    public function mcpWriteEnabled(): bool
    {
        return (bool) $this->getAttribute('mcp_write_enabled');
    }

    /** Still going through first-run onboarding (brand-new workspace). */
    public function isOnboarding(): bool
    {
        return $this->onboarding_completed_at === null;
    }

    /** GDPR deletion was requested and the workspace is pending hard-purge. */
    public function isPendingDeletion(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    /** When the irreversible purge happens (request time + grace window). */
    public function deletionPurgeAt(): ?\Carbon\CarbonInterface
    {
        if ($this->deletion_requested_at === null) {
            return null;
        }

        $days = (int) config('services.account.deletion_grace_days', 30);

        return $this->deletion_requested_at->copy()->addDays($days);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot(['role', 'membership_type', 'permissions'])
            ->withTimestamps();
    }

    /** Resolve the workspace owner (the `owner` pivot role), falling back to any member. */
    public function owner(): ?\App\Models\User
    {
        return $this->users()->wherePivot('role', 'owner')->first() ?? $this->users()->first();
    }

    public function googleAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GoogleAccount::class, 'workspace_id');
    }

    /** Auto top-up is switched on for this workspace (data column, default off). */
    public function autoRechargeEnabled(): bool
    {
        return (bool) $this->getAttribute('auto_recharge_enabled');
    }

    /** Balance at/below which auto top-up fires (data column, default 5). */
    public function autoRechargeThreshold(): int
    {
        return (int) ($this->getAttribute('auto_recharge_threshold') ?? 5);
    }

    /** Credit pack key bought on auto top-up (legacy; superseded by amount). */
    public function autoRechargePack(): string
    {
        return (string) ($this->getAttribute('auto_recharge_pack') ?: 'small');
    }

    /** Number of credits bought on each auto top-up (data column, default 50). */
    public function autoRechargeAmount(): int
    {
        return (int) ($this->getAttribute('auto_recharge_amount') ?? 50);
    }

    /** Email Cashier uses for the Stripe customer + Checkout prefill. */
    public function stripeEmail(): ?string
    {
        return $this->contact_email ?: once(fn () => $this->users()->first())?->email;
    }

    /** Name shown on the Stripe customer + invoices. */
    public function stripeName(): ?string
    {
        return $this->name;
    }

    /** Uploaded company logo URL (used in the workspace switcher), or null. */
    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? \Illuminate\Support\Facades\Storage::disk('uploads')->url($this->logo_path)
            : null;
    }

    /** 1–2 letter initials for the default circle avatar. */
    public function initials(): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim((string) $this->name)) ?: []));
        $first = mb_substr($words[0] ?? '', 0, 1);
        $last = count($words) > 1 ? mb_substr((string) end($words), 0, 1) : '';

        return mb_strtoupper($first.$last) ?: '?';
    }

    /** Deterministic colour for the default circle avatar (from the workspace id). */
    public function avatarColor(): string
    {
        $palette = ['#1800ff', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'];

        return $palette[abs(crc32((string) $this->id)) % count($palette)];
    }
}
