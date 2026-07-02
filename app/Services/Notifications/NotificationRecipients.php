<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;

/**
 * Resolves who should receive a given notification category for a workspace.
 * Recipients are workspace members (any role, including no-login Guests) chosen
 * on the Notifications settings page and stored as a category => [user ids] map
 * in the workspace `data` JSON. When a category has no explicit routing, it
 * falls back to the workspace owner so nothing silently goes undelivered.
 */
class NotificationRecipients
{
    public const ROUTES_KEY = 'notification_routes';

    /** Group tokens stored alongside individual user ids in a selection. */
    public const EVERYONE = 'everyone';

    public const ROLE_PREFIX = 'role:';

    /**
     * @return Collection<int, User> members with a deliverable email
     */
    public function for(Workspace $workspace, string $category): Collection
    {
        $selected = $this->routes($workspace)[$category] ?? null;

        if (! is_array($selected) || $selected === []) {
            $owner = $workspace->owner();
            $recipients = $owner !== null ? collect([$owner]) : collect();
        } else {
            $members = $workspace->users()->get();
            $ids = $this->expandSelection($selected, $members);
            $recipients = $members->whereIn('id', $ids)->values();
        }

        return $recipients->filter(fn (User $user): bool => filled($user->email))->values();
    }

    /**
     * Expand a saved recipient selection into concrete member ids. Entries are
     * either a user id, the literal "everyone", or a "role:<name>" group token;
     * groups resolve against each member's workspace_user.role pivot.
     *
     * @param  array<int, int|string>  $selected
     * @param  Collection<int, mixed>  $members  workspace members with a `pivot->role`
     * @return list<int>
     */
    public function expandSelection(array $selected, Collection $members): array
    {
        $ids = [];

        foreach ($selected as $entry) {
            if ($entry === self::EVERYONE) {
                foreach ($members as $member) {
                    $ids[] = (int) $member->id;
                }
            } elseif (is_string($entry) && str_starts_with($entry, self::ROLE_PREFIX)) {
                $role = substr($entry, strlen(self::ROLE_PREFIX));
                foreach ($members as $member) {
                    if (($member->pivot->role ?? null) === $role) {
                        $ids[] = (int) $member->id;
                    }
                }
            } else {
                $ids[] = (int) $entry;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * The full category => [user ids] routing map for the workspace.
     *
     * @return array<string, list<int>>
     */
    public function routes(Workspace $workspace): array
    {
        $value = $workspace->getAttribute(self::ROUTES_KEY);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }
}
