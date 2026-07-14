<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Services\Workspaces\InvitationAcceptor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Accept a workspace invitation via its token link. The flow handles three
 * cases: the invitee is logged in with the matching account (accept now), the
 * invitee has an account but isn't logged in (send to login), or the invitee
 * has no account yet (stash the token + send to registration, which attaches
 * them on sign-up instead of provisioning a fresh workspace).
 */
class InvitationController extends Controller
{
    public function show(string $token): View|RedirectResponse|Response
    {
        $invitation = Invitation::query()->where('token', $token)->first();

        // Render these pages in the language the invite was sent in.
        $this->applyInvitationLocale($invitation);

        if ($invitation === null || ! $invitation->isPending()) {
            return response()->view('invitations.invalid', [], 410);
        }

        // Park the token for the WHOLE auth surface, not only the brand-new-
        // invitee branch: with it in session, registration (email + Google) is
        // locked to the invited address everywhere. Without this, an invitee
        // whose address already had an account could wander from the login
        // page to sign-up and register a third, unrelated account.
        session(['pending_invite' => $invitation->token]);

        $user = auth()->user();

        // Already signed in as the invited person → show the accept button.
        if ($user !== null && $this->emailsMatch($user->email, $invitation->email)) {
            return view('invitations.show', ['invitation' => $invitation->load('workspace')]);
        }

        // Signed in as a DIFFERENT user (e.g. the owner opening the link they
        // sent) → not an error: explain who it's for. A plain 200 view keeps
        // it out of the error monitor and off the "something broke" path.
        if ($user !== null) {
            return view('invitations.wrong-account', [
                'invitation' => $invitation,
                'currentEmail' => (string) $user->email,
            ]);
        }

        // Not signed in: route by whether an account already exists.
        if (User::query()->where('email', $invitation->email)->exists()) {
            session(['url.intended' => route('invite.show', $invitation->token)]);

            return redirect('/login');
        }

        // Brand-new invitee → registration will attach them on sign-up.
        return redirect('/register');
    }

    public function accept(string $token): RedirectResponse|Response
    {
        $invitation = Invitation::query()->where('token', $token)->first();
        $user = auth()->user();

        $this->applyInvitationLocale($invitation);

        // Workspace gone (deleted after the invite went out) → the invite can
        // never be accepted; don't bounce back to the accept button forever.
        if ($invitation !== null && $invitation->workspace === null) {
            return response()->view('invitations.invalid', [], 410);
        }

        if ($invitation === null || $user === null
            || ! app(InvitationAcceptor::class)->accept($user, $invitation)) {
            return redirect()->route('invite.show', $token);
        }

        session()->forget('pending_invite');

        return redirect('/');
    }

    private function emailsMatch(?string $a, ?string $b): bool
    {
        return app(InvitationAcceptor::class)->emailsMatch($a, $b);
    }

    /**
     * These pages have no tenant/session context, so the app locale defaults to
     * English. An explicit choice via the language switcher (session) wins,
     * then the language the invitation was sent in, then the visitor's browser
     * preference (used when the token is unknown).
     */
    private function applyInvitationLocale(?Invitation $invitation): void
    {
        $supported = ['en', 'de'];

        $locale = session('locale');
        if (! in_array($locale, $supported, true)) {
            $locale = $invitation?->locale;
        }
        if (! in_array($locale, $supported, true)) {
            $locale = request()->getPreferredLanguage($supported);
        }

        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
        }
    }
}
