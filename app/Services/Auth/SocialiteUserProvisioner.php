<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

/**
 * Auto-provisions a brand-new user that signed up via a social provider (Google).
 *
 * IMPORTANT: this runs INSIDE filament-socialite's DB::transaction, so it must
 * only do plain inserts. The workspace is intentionally NOT provisioned here —
 * creating a tenant fires stancl's CREATE DATABASE, a MySQL DDL statement that
 * implicitly commits the wrapping transaction and makes its final commit throw
 * "There is no active transaction". Instead, SetCurrentWorkspace self-heals on
 * the first authenticated request: a user with no workspace gets one provisioned
 * there, outside any transaction.
 */
class SocialiteUserProvisioner
{
    public function create(SocialiteUserContract $oauthUser): User
    {
        $user = User::create([
            // Google may omit a name; fall back to the nickname, then a generic label.
            'name' => $oauthUser->getName() ?: ($oauthUser->getNickname() ?: 'New user'),
            'email' => $oauthUser->getEmail(),
            // password is a NOT NULL column; Google users have none, so set a
            // random unusable one (the User model hashes it via the 'hashed' cast).
            'password' => Str::password(32),
        ]);

        // Google emails are verified — mark verified to avoid a verification gate.
        // (email_verified_at is guarded, so set it outside mass-assignment.)
        $user->forceFill(['email_verified_at' => now()])->save();

        $beta = app(BetaAccess::class);

        // Private beta: unknown emails only apply for access (EnsureBetaApproved
        // shows the pending screen); allowlisted ones start right away. Both
        // paths are plain inserts/updates, transaction-safe.
        if (! $beta->grantsImmediateAccess((string) $user->email)) {
            $beta->sendReceivedEmail($user);

            return $user;
        }

        $user->forceFill(['approved_at' => now()])->save();

        try {
            Mail::to($user->email)->send(new WelcomeMail($user->name, $user->locale ?? 'en'));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed', ['error' => $e->getMessage()]);
        }

        return $user;
    }
}
