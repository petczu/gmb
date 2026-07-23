<?php

declare(strict_types=1);

namespace App\Services\Workspaces;

use App\Models\Invitation;
use App\Models\User;
use App\Support\Locales;
use Spatie\Permission\PermissionRegistrar;

/**
 * Attaches a user to the workspace an invitation points at: membership pivot
 * (role + location scope), spatie role, invitation accepted, inviter's locale
 * adopted, beta approval granted (the owner vouched for them), and the session
 * switched to that workspace. Shared by every path that can accept an invite:
 * the public accept button, email-OTP registration, and Google sign-up.
 *
 * Uses only plain inserts/updates on the central DB, so it is safe to run
 * inside filament-socialite's DB::transaction (no tenant-DB DDL).
 */
class InvitationAcceptor
{
    /** Returns false when the invitation can't be accepted by this user. */
    public function accept(User $user, Invitation $invitation): bool
    {
        if (! $invitation->isPending() || ! $this->emailsMatch($user->email, $invitation->email)) {
            return false;
        }

        $workspace = $invitation->workspace;
        if ($workspace === null) {
            return false;
        }

        // The inviter's location scope drives both access and location-scoped
        // notification routing; empty = all locations.
        $locationIds = array_values(array_map('intval', (array) ($invitation->location_ids ?? [])));

        $workspace->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $invitation->role,
                'membership_type' => 'internal',
                'permissions' => json_encode(['allowed_locations' => $locationIds]),
            ],
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        $user->unsetRelation('roles');
        $user->syncRoles([$invitation->role]);

        // Adopt the language the inviter picked (notifications, reports). The
        // member can switch it anytime via the UI language switcher.
        if (in_array($invitation->locale, Locales::codes(), true) && $user->getAttribute('locale') !== $invitation->locale) {
            $user->forceFill(['locale' => $invitation->locale])->save();
        }

        $invitation->forceFill(['accepted_at' => now()])->save();

        // An accepted invitation IS the beta approval: the workspace owner
        // vouched for this person, so they never sit on the waitlist.
        if ($user->approved_at === null) {
            $user->forceFill(['approved_at' => now()])->save();
        }

        session(['current_workspace_id' => $workspace->id]);

        return true;
    }

    public function emailsMatch(?string $a, ?string $b): bool
    {
        return $a !== null && $b !== null && mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }

    /**
     * The pending invitation this visitor arrived with (session token from the
     * invite link). Null when there is none or it is no longer pending.
     */
    public function pendingFromSession(): ?Invitation
    {
        $token = session('pending_invite');
        if (! is_string($token) || $token === '') {
            return null;
        }

        $invitation = Invitation::query()->where('token', $token)->first();

        return ($invitation !== null && $invitation->isPending()) ? $invitation : null;
    }

    /**
     * p.czuiko@gmail.com → p***@gmail.com — enough for the invitee to recognize
     * their own address without exposing it to whoever else opens the link.
     */
    public static function maskEmail(string $email): string
    {
        $email = trim($email);
        $at = mb_strrpos($email, '@');
        if ($at === false || $at < 1) {
            return '***';
        }

        return mb_substr($email, 0, 1).'***'.mb_substr($email, $at);
    }
}
