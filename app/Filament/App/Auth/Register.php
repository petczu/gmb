<?php

declare(strict_types=1);

namespace App\Filament\App\Auth;

use App\Mail\WelcomeMail;
use App\Models\Invitation;
use App\Services\Workspaces\WorkspaceProvisioner;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Registration: collects the person + their company name, creates the user, then
 * provisions a fresh workspace (tenant) owned by them. The new workspace starts
 * with onboarding_completed_at = null, so the app shows the onboarding guide
 * (company details → plan → first location) on first sign-in.
 */
class Register extends BaseRegister
{
    /**
     * Two-step sign-up, matching the login page: step 1 asks only for the
     * email (or a social login), step 2 collects name, company and password.
     */
    public int $step = 1;

    public function form(Schema $schema): Schema
    {
        $onDetailsStep = fn (): bool => $this->step === 2;

        return $schema
            ->components([
                $this->getEmailFormComponent()
                    ->readOnly($onDetailsStep)
                    ->hintAction(
                        Action::make('changeEmail')
                            ->label(__('auth.change_email'))
                            ->visible($onDetailsStep)
                            ->action(fn () => $this->step = 1),
                    ),
                $this->getNameFormComponent()->visible($onDetailsStep),
                TextInput::make('company')
                    ->label(__('auth.company_name'))
                    ->required()
                    ->maxLength(120)
                    ->visible($onDetailsStep),
                $this->getPasswordFormComponent()->visible($onDetailsStep),
                $this->getPasswordConfirmationFormComponent()->visible($onDetailsStep),
            ]);
    }

    /** Step 1 submits advance to the details step; step 2 actually registers. */
    public function register(): ?RegistrationResponse
    {
        if ($this->step === 1) {
            $this->validateEmailStep();
            $this->step = 2;

            return null;
        }

        return parent::register();
    }

    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label(fn (): string => $this->step === 1
                ? __('auth.continue_with_email')
                : __('filament-panels::auth/pages/register.form.actions.register.label'));
    }

    /** Early email validation so step 1 fails fast on typos/taken addresses. */
    protected function validateEmailStep(): void
    {
        $email = (string) data_get($this->form->getRawState(), 'email');

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'string', 'email', 'max:255',
                Rule::unique($this->getUserModel(), 'email')]],
            [],
            ['email' => __('filament-panels::auth/pages/register.form.email.label')],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'data.email' => $validator->errors()->first('email'),
            ]);
        }
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

        $invitation = Invitation::query()->where('token', $token)->first();

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

        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        $user->syncRoles([$invitation->role]);

        $invitation->forceFill(['accepted_at' => now()])->save();

        session(['current_workspace_id' => $workspace->id]);
        session()->forget('pending_invite');

        return true;
    }

    protected function sendWelcomeEmail(Model $user): void
    {
        try {
            Mail::to($user->email)
                ->send(new WelcomeMail($user->name, $user->locale ?? 'en'));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed', ['error' => $e->getMessage()]);
        }
    }
}
