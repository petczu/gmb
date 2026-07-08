<?php

declare(strict_types=1);

namespace App\Filament\App\Auth;

use App\Mail\WelcomeMail;
use App\Models\Invitation;
use App\Services\Auth\EmailOtp;
use App\Services\Auth\TooManyCodeRequests;
use App\Services\Workspaces\WorkspaceProvisioner;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Passwordless sign-up, matching the Google flow: step 1 asks only for the
 * email (or a social login), step 2 verifies a 6-digit code sent to that email.
 * The account is created right after the code checks out (email-verified, random
 * unusable password, name derived from the address). Everything else happens
 * later: company/plan/location in the onboarding wizard, name + password in the
 * profile. Invited users are attached to the inviter's workspace instead of
 * getting their own.
 */
class Register extends BaseRegister
{
    public int $step = 1;

    public function form(Schema $schema): Schema
    {
        $onCodeStep = fn (): bool => $this->step === 2;

        return $schema
            ->components([
                $this->getEmailFormComponent()
                    ->readOnly($onCodeStep)
                    ->hintAction(
                        Action::make('changeEmail')
                            ->label(__('auth.change_email'))
                            ->visible($onCodeStep)
                            ->action(function (): void {
                                $this->step = 1;
                                $this->data['code'] = null;
                            }),
                    ),

                TextInput::make('code')
                    ->label(__('auth.code_label'))
                    ->helperText(fn (): string => __('auth.code_help', [
                        'email' => (string) data_get($this->form->getRawState(), 'email'),
                    ]))
                    ->required()
                    ->numeric()
                    ->length(6)
                    ->autocomplete('one-time-code')
                    ->extraInputAttributes(['inputmode' => 'numeric'])
                    ->visible($onCodeStep)
                    ->hintAction(
                        Action::make('resendCode')
                            ->label(__('auth.resend_code'))
                            ->visible($onCodeStep)
                            ->action('resendCode'),
                    ),
            ]);
    }

    /** Step 1 sends the code; step 2 verifies it and creates the account. */
    public function register(): ?RegistrationResponse
    {
        if ($this->step === 1) {
            $this->validateEmailStep();
            $this->sendCode();
            $this->step = 2;

            return null;
        }

        $this->validateCode();

        return parent::register();
    }

    public function resendCode(): void
    {
        $this->sendCode();

        Notification::make()->title(__('auth.code_resent'))->success()->send();
    }

    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()
            ->label(fn (): string => $this->step === 1
                ? __('auth.continue_with_email')
                : __('auth.create_account'));
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

    protected function sendCode(): void
    {
        $email = (string) data_get($this->form->getRawState(), 'email');

        try {
            app(EmailOtp::class)->send($email, app()->getLocale());
        } catch (TooManyCodeRequests) {
            throw ValidationException::withMessages([
                'data.email' => __('auth.code_throttled'),
            ]);
        }
    }

    protected function validateCode(): void
    {
        $state = $this->form->getRawState();
        $email = (string) data_get($state, 'email');
        $code = (string) data_get($state, 'code');

        if (! app(EmailOtp::class)->verify($email, $code)) {
            throw ValidationException::withMessages([
                'data.code' => __('auth.code_invalid'),
            ]);
        }
    }

    protected function handleRegistration(array $data): Model
    {
        $email = (string) $data['email'];

        $user = $this->getUserModel()::create([
            // A real name comes later (profile); default to the address's local
            // part so greetings aren't empty.
            'name' => Str::ucfirst(Str::before($email, '@')),
            'email' => $email,
            // Password column is NOT NULL; set a random unusable one (hashed by
            // the model cast). The user can set a real one in the profile.
            'password' => Str::password(32),
        ]);

        // The code proved mailbox ownership — mark verified, same as Google.
        $user->forceFill(['email_verified_at' => now()])->save();

        // Invited sign-up: attach to the inviter's workspace instead of
        // provisioning an empty one of their own.
        if ($this->attachToPendingInvite($user)) {
            $this->sendWelcomeEmail($user);

            return $user;
        }

        $workspace = app(WorkspaceProvisioner::class)->create($user, '');
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
