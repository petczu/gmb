<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Super-admin user deletion (admin panel). Removes the account and everything
 * that would be orphaned by it:
 *
 *  - workspaces where this user is the ONLY member are purged via
 *    WorkspaceDeletionService (tenant DB drop, Zernio profile, Stripe cancel);
 *    shared workspaces are kept and the user is just detached
 *  - central rows keyed to the user (sessions, OAuth tokens, AI conversations,
 *    spatie role assignments); FK cascades cover the pivot/socialite/drip rows
 *
 * Super admins can never be deleted here: guard at the call site AND in this
 * service, so a stray call can't take down the operator account.
 */
class UserDeletionService
{
    public function __construct(private readonly WorkspaceDeletionService $workspaces) {}

    /** Workspaces that would be purged with this user (they are the sole member). */
    public function soleWorkspaces(User $user): array
    {
        return $user->workspaces()
            ->get()
            ->filter(fn (Workspace $workspace): bool => $workspace->users()->count() === 1)
            ->values()
            ->all();
    }

    public function delete(User $user): void
    {
        if ($user->isSuperAdmin()) {
            throw new \LogicException('Super admin accounts cannot be deleted.');
        }

        $id = $user->id;

        // Purge workspaces nobody else belongs to; detach from shared ones.
        foreach ($this->soleWorkspaces($user) as $workspace) {
            $this->workspaces->purge($workspace);
        }
        $user->workspaces()->detach();

        // Central rows without FK cascades.
        DB::table('sessions')->where('user_id', $id)->delete();
        DB::table('oauth_access_tokens')->where('user_id', $id)->delete();
        DB::table('oauth_auth_codes')->where('user_id', $id)->delete();
        DB::table('oauth_device_codes')->where('user_id', $id)->delete();

        $conversationIds = DB::table('agent_conversations')->where('user_id', $id)->pluck('id');
        DB::table('agent_conversation_messages')->whereIn('conversation_id', $conversationIds)->delete();
        DB::table('agent_conversations')->where('user_id', $id)->delete();

        // spatie role/permission assignments (any team).
        DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $id)->delete();
        DB::table('model_has_permissions')->where('model_type', User::class)->where('model_id', $id)->delete();

        // Cascades: workspace_user, socialite_users, drip_emails.
        // invitations.invited_by nulls out.
        $user->delete();

        Log::info('User deleted by super admin', ['user' => $id, 'email' => $user->email]);
    }
}
