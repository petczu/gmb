<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\PermissionRegistrar;

/**
 * Accept a workspace invitation via its token link. The flow handles three
 * cases: the invitee is logged in with the matching account (accept now), the
 * invitee has an account but isn't logged in (send to login), or the invitee
 * has no account yet (stash the token + send to registration, which attaches
 * them on sign-up instead of provisioning a fresh workspace).
 */
class InvitationController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $invitation = Invitation::query()->where('token', $token)->first();

        if ($invitation === null || ! $invitation->isPending()) {
            return response()->view('invitations.invalid', [], 410);
        }

        $user = auth()->user();

        // Already signed in as the invited person → show the accept button.
        if ($user !== null && $this->emailsMatch($user->email, $invitation->email)) {
            return view('invitations.show', ['invitation' => $invitation->load('workspace')]);
        }

        // Signed in as a DIFFERENT user → can't accept someone else's invite.
        if ($user !== null) {
            return response()->view('invitations.invalid', [
                'title' => 'Wrong account',
                'message' => "This invitation was sent to {$invitation->email}. Sign out and sign in with that email to accept it.",
            ], 403);
        }

        // Not signed in: route by whether an account already exists.
        if (User::query()->where('email', $invitation->email)->exists()) {
            session(['url.intended' => route('invite.show', $invitation->token)]);

            return redirect('/login');
        }

        // Brand-new invitee → registration will attach them on sign-up.
        session(['pending_invite' => $invitation->token]);

        return redirect('/register');
    }

    public function accept(string $token): RedirectResponse
    {
        $invitation = Invitation::query()->where('token', $token)->first();
        $user = auth()->user();

        if ($invitation === null || ! $invitation->isPending() || $user === null
            || ! $this->emailsMatch($user->email, $invitation->email)) {
            return redirect()->route('invite.show', $token);
        }

        $workspace = $invitation->workspace;
        if ($workspace === null) {
            return response()->view('invitations.invalid', [], 410);
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
        if (in_array($invitation->locale, ['en', 'de'], true) && $user->getAttribute('locale') !== $invitation->locale) {
            $user->forceFill(['locale' => $invitation->locale])->save();
        }

        $invitation->forceFill(['accepted_at' => now()])->save();

        // An accepted invitation IS the beta approval: the workspace owner
        // vouched for this person, so they never sit on the waitlist.
        if ($user->approved_at === null) {
            $user->forceFill(['approved_at' => now()])->save();
        }

        session(['current_workspace_id' => $workspace->id]);

        return redirect('/');
    }

    private function emailsMatch(?string $a, ?string $b): bool
    {
        return $a !== null && $b !== null && mb_strtolower(trim($a)) === mb_strtolower(trim($b));
    }
}
