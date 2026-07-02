<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Mail\WorkspaceDeletionMail;
use App\Models\GoogleAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * GDPR account deletion for a workspace (the tenant that holds reviews, AI
 * content, locations and the Stripe subscription).
 *
 * Flow:
 *   request()       soft-lock + stop processing (cancel subscription, unlink
 *                   Google/Zernio). Recoverable for the grace window.
 *   cancelRequest() undo within the grace window ("Cancel deletion").
 *   purge()         irreversible: drop the tenant DB + central links. Stripe
 *                   invoices are intentionally kept (tax-law retention).
 */
class WorkspaceDeletionService
{
    public function __construct(private readonly LocationBilling $billing) {}

    /** Owner requested deletion: lock the workspace and stop all processing. */
    public function request(Workspace $workspace, User $by): void
    {
        $workspace->deletion_requested_at = now();
        $workspace->deletion_requested_by = $by->id;
        $workspace->save();

        // Stop billing: cancel at period end (keeps the door open for a
        // "Cancel deletion" → resume() during the grace window).
        try {
            $subscription = $this->billing->subscription($workspace);
            if ($subscription !== null && $subscription->active()) {
                $subscription->cancel();
            }
        } catch (Throwable $e) {
            Log::warning('Workspace deletion: could not cancel subscription', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Stop pulling Google data via Zernio — unlink connected accounts.
        $workspace->googleAccounts()->delete();

        $this->notify($workspace, $by, 'scheduled');

        Log::info('Workspace deletion requested', [
            'workspace' => $workspace->id,
            'by' => $by->id,
            'purge_at' => $workspace->deletionPurgeAt()?->toIso8601String(),
        ]);
    }

    /** Undo a pending deletion within the grace window. */
    public function cancelRequest(Workspace $workspace): void
    {
        $workspace->deletion_requested_at = null;
        $workspace->deletion_requested_by = null;
        $workspace->save();

        // Resume the subscription if it is still on its cancellation grace.
        try {
            $subscription = $this->billing->subscription($workspace);
            if ($subscription !== null && $subscription->onGracePeriod()) {
                $subscription->resume();
            }
        } catch (Throwable $e) {
            Log::warning('Workspace deletion cancel: could not resume subscription', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Workspace deletion cancelled', ['workspace' => $workspace->id]);
    }

    /**
     * Irreversibly purge the workspace. Drops the tenant database and removes
     * central links. Stripe invoices are kept (legal retention); we only cancel
     * any still-active subscription.
     */
    public function purge(Workspace $workspace): void
    {
        $id = $workspace->id;

        // Capture who to notify before the workspace is gone.
        $requester = $workspace->deletion_requested_by
            ? User::find($workspace->deletion_requested_by)
            : null;

        // Hard-cancel any subscription that survived (e.g. grace ended).
        try {
            $subscription = $this->billing->subscription($workspace);
            if ($subscription !== null && ! $subscription->canceled()) {
                $subscription->cancelNow();
            }
        } catch (Throwable $e) {
            Log::warning('Workspace purge: subscription cancelNow failed', [
                'workspace' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        // Central links/PII tied to this workspace.
        GoogleAccount::where('workspace_id', $id)->delete();
        $workspace->users()->detach();

        // spatie roles/permissions scoped to this team (team_id = workspace id).
        DB::table('model_has_roles')->where('team_id', $id)->delete();
        DB::table('model_has_permissions')->where('team_id', $id)->delete();
        DB::table('roles')->where('team_id', $id)->delete();

        // Deleting the tenant drops its database (TenantDeleted → DeleteDatabase).
        $workspace->delete();

        if ($requester !== null) {
            $this->notify($workspace, $requester, 'completed');
        }

        Log::info('Workspace purged (irreversible)', ['workspace' => $id]);
    }

    private function notify(Workspace $workspace, User $user, string $stage): void
    {
        if (! $user->email) {
            return;
        }

        try {
            Mail::to($user->email)->queue(new WorkspaceDeletionMail(
                workspaceName: $workspace->name,
                stage: $stage,
                purgeAt: $workspace->deletionPurgeAt(),
            ));
        } catch (Throwable $e) {
            Log::warning('Workspace deletion email failed', [
                'workspace' => $workspace->id,
                'stage' => $stage,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
