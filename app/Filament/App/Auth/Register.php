<?php

declare(strict_types=1);

namespace App\Filament\App\Auth;

use App\Services\Workspaces\WorkspaceProvisioner;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Registration: collects the person + their company name, creates the user, then
 * provisions a fresh workspace (tenant) owned by them. The new workspace starts
 * with onboarding_completed_at = null, so the app shows the onboarding guide
 * (company details → plan → first location) on first sign-in.
 */
class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                TextInput::make('company')
                    ->label(__('auth.company_name'))
                    ->required()
                    ->maxLength(120),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $company = $data['company'] ?? null;
        unset($data['company']); // not a User attribute

        $user = $this->getUserModel()::create($data);

        // Invited sign-up: attach to the inviter's workspace instead of
        // provisioning an empty one of their own.
        if ($this->attachToPendingInvite($user)) {
            $this->sendWelcomeEmail($user);

            return $user;
        }

        $workspace = app(WorkspaceProvisioner::class)->create($user, (string) $company);
        session(['current_workspace_id' => $workspace->id]);

        $this->sendWelcomeEmail($user);

        return $user;
    }

    /**
     * If a valid pending invitation matches this user's email, attach them to
     * that workspace + assign the invited role and mark it accepted. Returns
     * true when handled (so no new workspace is provisioned).
     */
    protected function attachToPendingInvite(Model $user): bool
    {
        $token = session('pending_invite');
        if (! is_string($token) || $token === '') {
            return false;
        }

        $invitation = \App\Models\Invitation::query()->where('token', $token)->first();

        if ($invitation === null
            || ! $invitation->isPending()
            || mb_strtolower(trim($invitation->email)) !== mb_strtolower(trim((string) $user->email))) {
            session()->forget('pending_invite');

            return false;
        }

        $workspace = $invitation->workspace;
        if ($workspace === null) {
            session()->forget('pending_invite');

            return false;
        }

        $workspace->users()->syncWithoutDetaching([
            $user->getKey() => ['role' => $invitation->role, 'membership_type' => 'internal'],
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        $user->syncRoles([$invitation->role]);

        $invitation->forceFill(['accepted_at' => now()])->save();

        session(['current_workspace_id' => $workspace->id]);
        session()->forget('pending_invite');

        return true;
    }

    protected function sendWelcomeEmail(Model $user): void
    {
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)
                ->send(new \App\Mail\WelcomeMail($user->name, $user->locale ?? 'en'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Welcome email failed', ['error' => $e->getMessage()]);
        }
    }
}
