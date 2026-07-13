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
            'drip_connect' => 1,
            'drip_inbox' => 1,
            'drip_automation' => 3,
            'drip_growth' => 5,
            'drip_competitors' => 5,
            'drip_reports' => 7,
            'drip_team' => 10,
        ],
        'member' => [
            'drip_member' => 1,
        ],
    ];

    /**
     * Conditional steps: each is an activation nudge that only makes sense
     * while the matching feature is still unused. The step is skipped when the
     * workspace-state flag (supplied by the emails:drip command) is true.
     */
    public const CONDITIONS = [
        'drip_connect' => 'has_locations',
        'drip_automation' => 'has_automations',
        'drip_growth' => 'has_active_review_page',
        'drip_competitors' => 'has_competitors',
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

    /**
     * Excluded from product education: guests (notification-only contacts,
     * no login) and users without any workspace membership at all (a removed
     * guest, or an invitee who has not accepted yet) — there is nothing to
     * onboard either of them into.
     */
    public function isGuestOnly(User $user): bool
    {
        $types = $user->workspaces()->pluck('workspace_user.membership_type');

        return $types->isEmpty() || $types->every(fn ($t): bool => $t === 'guest');
    }

    /**
     * The single next step due for this user right now, or null.
     *
     * $state carries the workspace-state flags for the CONDITIONS map (the
     * command passes the real values); a missing flag defaults to true, which
     * keeps the conditional nudges out of the way.
     *
     * @param  list<string>  $alreadySent
     * @param  array<string, bool>  $state
     */
    public function dueStep(User $user, array $alreadySent, ?CarbonInterface $now = null, array $state = []): ?string
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

            $condition = self::CONDITIONS[$key] ?? null;
            if ($condition !== null && ($state[$condition] ?? true)) {
                continue;
            }

            if ($days >= $day && $days <= $day + self::WINDOW_DAYS) {
                return $key;
            }
        }

        return null;
    }
}
