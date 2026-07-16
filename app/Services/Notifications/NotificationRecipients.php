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
 *
 * Location scoping: a member/guest whose pivot `permissions.allowed_locations`
 * is non-empty only receives notifications about those locations. A
 * location-specific notification passes its `$locationId` here; recipients
 * restricted to other locations are dropped. Empty restriction = all locations
 * (unchanged behaviour). The owner-fallback always delivers.
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
    public function for(Workspace $workspace, string $category, ?int $locationId = null): Collection
    {
        $selection = $this->normalizeSelection($this->routes($workspace)[$category] ?? null);

        if ($selection['include'] === []) {
            // No explicit routing → the owner gets it, for every location.
            $owner = $workspace->owner();
            $recipients = $owner !== null ? collect([$owner]) : collect();
        } else {
            $members = $workspace->users()->get();
            $ids = $this->resolveIds($selection, $members);
            $recipients = $members
                ->whereIn('id', $ids)
                ->filter(fn (User $user): bool => $this->coversLocation($user, $locationId))
                ->values();
        }

        return $recipients->filter(fn (User $user): bool => filled($user->email))->values();
    }

    /**
     * Does this member's location restriction cover the given location? True
     * when there is no location context, no restriction (empty = all), or the
     * location is in the member's allowed list.
     */
    private function coversLocation(User $member, ?int $locationId): bool
    {
        return self::locationAllowed($member->pivot->permissions ?? null, $locationId);
    }

    /**
     * Pure rule for whether a member's pivot `permissions` cover a location.
     * Null location = no context (all pass); empty allowed_locations = all
     * locations; otherwise the location must be in the allowed list.
     */
    public static function locationAllowed(mixed $permissions, ?int $locationId): bool
    {
        if ($locationId === null) {
            return true;
        }

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        $allowed = is_array($permissions) ? ($permissions['allowed_locations'] ?? []) : [];
        $allowed = is_array($allowed) ? array_map('intval', $allowed) : [];

        return $allowed === [] || in_array($locationId, $allowed, true);
    }

    /**
     * A stored selection is either a legacy flat list of tokens (all included)
     * or an {include, exclude} shape. Normalize to the latter.
     *
     * @return array{include: list<int|string>, exclude: list<int|string>}
     */
    public function normalizeSelection(mixed $selection): array
    {
        if (! is_array($selection)) {
            return ['include' => [], 'exclude' => []];
        }

        if (array_key_exists('include', $selection) || array_key_exists('exclude', $selection)) {
            return [
                'include' => array_values((array) ($selection['include'] ?? [])),
                'exclude' => array_values((array) ($selection['exclude'] ?? [])),
            ];
        }

        // Legacy flat list = everything is included, nothing excluded.
        return ['include' => array_values($selection), 'exclude' => []];
    }

    /**
     * Concrete member ids for a selection: expand the included groups/people,
     * then remove anyone the excluded groups/people resolve to. So "role:admin"
     * included minus one person excluded means "all admins except that person".
     *
     * @param  mixed  $selection  a flat list or {include, exclude} shape
     * @param  Collection<int, mixed>  $members
     * @return list<int>
     */
    public function resolveIds(mixed $selection, Collection $members): array
    {
        $selection = $this->normalizeSelection($selection);
        $include = $this->expandSelection($selection['include'], $members);
        $exclude = $this->expandSelection($selection['exclude'], $members);

        return array_values(array_diff($include, $exclude));
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
