<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * The onboarding email series: which product-education email a user is due,
 * based on how they joined (created the account vs was invited) and how many
 * days ago. Pure selection logic — sending/dedup lives in the emails:drip
 * command; content lives in the editable email templates (Onboarding series).
 */
class DripSeries
{
    /** Step key => day offset since signup, per track. */
    public const TRACKS = [
        'owner' => [
            // Activation nudge: only sent while the workspace still has NO
            // locations (see dueStep's $hasLocations) — everything else in the
            // product is pointless before the first connection.
            'drip_connect' => 1,
            'drip_inbox' => 1,
            'drip_automation' => 3,
            'drip_growth' => 5,
            'drip_reports' => 7,
            'drip_team' => 10,
        ],
        'member' => [
            'drip_member' => 1,
        ],
    ];

    /**
     * A step stops being sent this many days after it became due, so users who
     * existed before the feature (or long-dormant signups) don't get a backlog
     * burst of every step at once.
     */
    public const WINDOW_DAYS = 3;

    /** @return list<string> every step key across tracks (for template sync/UI). */
    public static function keys(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::TRACKS)));
    }

    /** 'owner' when the user owns any workspace, otherwise 'member'. */
    public function trackFor(User $user): string
    {
        $roles = $user->workspaces()->pluck('workspace_user.role');

        return $roles->contains('owner') ? 'owner' : 'member';
    }

    /** Guests (notification-only contacts everywhere) get no product education. */
    public function isGuestOnly(User $user): bool
    {
        $types = $user->workspaces()->pluck('workspace_user.membership_type');

        return $types->isNotEmpty() && $types->every(fn ($t): bool => $t === 'guest');
    }

    /**
     * The single next step due for this user right now, or null.
     *
     * $hasLocations controls the conditional 'drip_connect' nudge: it is only
     * due while the owner's workspace has no locations (the command passes the
     * real state; default true keeps the step out of the way).
     *
     * @param  list<string>  $alreadySent
     */
    public function dueStep(User $user, array $alreadySent, ?CarbonInterface $now = null, bool $hasLocations = true): ?string
    {
        if (! $user->getAttribute('product_emails')) {
            return null;
        }

        if ($this->isGuestOnly($user)) {
            return null;
        }

        $now = $now ?? now();
        $days = (int) $user->created_at->startOfDay()->diffInDays($now->copy()->startOfDay());

        foreach (self::TRACKS[$this->trackFor($user)] as $key => $day) {
            if (in_array($key, $alreadySent, true)) {
                continue;
            }

            if ($key === 'drip_connect' && $hasLocations) {
                continue;
            }

            if ($days >= $day && $days <= $day + self::WINDOW_DAYS) {
                return $key;
            }
        }

        return null;
    }
}
