<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\BetaApprovedMail;
use App\Mail\BetaReceivedMail;
use App\Models\BetaAllowlistEntry;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Private beta access control. While beta mode is on (config/beta.php), new
 * sign-ups only apply for access unless their email is on the allowlist (or a
 * super admin). Applications are activated from the /admin panel, which sets
 * users.approved_at and emails the person.
 */
class BetaAccess
{
    public function enabled(): bool
    {
        return (bool) config('beta.enabled');
    }

    /** Whether this user may use the app (the beta gate check). */
    public function hasAccess(User $user): bool
    {
        return ! $this->enabled()
            || $user->approved_at !== null
            || $user->isSuperAdmin();
    }

    /** Whether a sign-up with this email skips the application queue. */
    public function grantsImmediateAccess(string $email): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        $email = mb_strtolower(trim($email));

        if (in_array($email, config('superadmin.emails', []), true)) {
            return true;
        }

        if (BetaAllowlistEntry::query()->where('email', $email)->exists()) {
            return true;
        }

        // People invited into an existing workspace were vouched for by its
        // owner; they never join the waitlist.
        return Invitation::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->exists();
    }

    /** Activate a pending application and tell the person by email. */
    public function approve(User $user, bool $notify = true): void
    {
        if ($user->approved_at !== null) {
            return;
        }

        $user->forceFill(['approved_at' => now()])->save();

        if (! $notify) {
            return;
        }

        try {
            Mail::to($user->email)->send(new BetaApprovedMail($user->name, $user->locale ?? 'en'));
        } catch (Throwable $e) {
            Log::warning('Beta approved email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /** Confirm a new application ("thanks, we will be in touch"). */
    public function sendReceivedEmail(User $user): void
    {
        try {
            Mail::to($user->email)->send(new BetaReceivedMail($user->name, $user->locale ?? 'en'));
        } catch (Throwable $e) {
            Log::warning('Beta received email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
