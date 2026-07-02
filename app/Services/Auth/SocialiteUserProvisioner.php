<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\Workspaces\WorkspaceProvisioner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

/**
 * Auto-provisions a brand-new user that signed up via a social provider (Google).
 *
 * Mirrors App\Filament\App\Auth\Register::handleRegistration: create the user,
 * provision their own workspace (tenant) with the fallback name, set the session
 * workspace so the redirect initializes that tenant, and send the welcome email.
 *
 * The filament-socialite controller calls this from inside a DB transaction and
 * persists the SocialiteUser pivot itself afterwards, so this only returns the User.
 */
class SocialiteUserProvisioner
{
    public function __construct(private readonly WorkspaceProvisioner $provisioner) {}

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

        // Replicate the password-signup provisioning: empty company name → the
        // WorkspaceProvisioner uses "{name}'s workspace" as the fallback.
        $workspace = $this->provisioner->create($user, '');
        session(['current_workspace_id' => $workspace->id]);

        try {
            Mail::to($user->email)->send(new WelcomeMail($user->name, $user->locale ?? 'en'));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed', ['error' => $e->getMessage()]);
        }

        return $user;
    }
}
