<?php

declare(strict_types=1);

namespace App\Filament\App\Auth;

use App\Billing\Plans;
use App\Mail\WelcomeMail;
use App\Models\Invitation;
use App\Models\LegalDocument;
use App\Services\Auth\BetaAccess;
use App\Services\Auth\EmailOtp;
use App\Services\Auth\TooManyCodeRequests;
use App\Services\Workspaces\WorkspaceProvisioner;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Passwordless sign-up, matching the Google flow: step 1 asks only for the
 * email (or a social login), step 2 verifies a 6-digit code sent to that email,
 * step 3 shows the Terms of Service in a scroll box — the button unlocks after
 * reading to the end, and accepting it creates the account (email-verified,
 * random unusable password, name derived from the address). Everything else
 * happens later: company/plan/location in the onboarding wizard, name +
 * password in the profile. Invited users are attached to the inviter's
 * workspace instead of getting their own.
 */
class Register extends BaseRegister
{
    public int $step = 1;

    /**
     * Deep link from the marketing site's pricing cards:
     * /register?plan=pro&interval=year. The choice is parked in the session so
     * it survives the OTP steps (and social login) and preselects the plan step
     * of the onboarding wizard.
     */
    public function mount(): void
    {
        parent::mount();

        $plan = strtolower((string) request()->query('plan'));
        if (Plans::find($plan) !== null) {
            session(['intended_plan' => $plan]);
        }

        $interval = match (strtolower((string) request()->query('interval'))) {
            'year', 'yearly', 'annual' => 'yearly',
            'month', 'monthly' => 'monthly',
            default => null,
        };
        if ($interval !== null) {
            session(['intended_interval' => $interval]);
        }
    }

    public function form(Schema $schema): Schema
    {
        $onCodeStep = fn (): bool => $this->step === 2;

        return $schema
            ->components([
                $this->getEmailFormComponent()
                    ->readOnly(fn (): bool => $this->step >= 2)
                    ->hintAction(
                        Action::make('changeEmail')
                            ->label(__('auth.change_email'))
                            ->visible(fn (): bool => $this->step >= 2)
                            ->action(function (): void {
                                $this->step = 1;
                                $this->data['code'] = null;
                                $this->data['terms_read'] = false;
                                session()->forget('register_otp_email');
                            }),
                    ),

                // Segmented one-time-code field, matching the 2FA challenge.
                OneTimeCodeInput::make('code')
                    ->label(__('auth.code_label'))
                    ->helperText(fn (): string => __('auth.code_help', [
                        'email' => (string) data_get($this->form->getRawState(), 'email'),
                    ]))
                    ->required()
                    ->length(6)
                    ->visible($onCodeStep)
                    ->hintAction(
                        Action::make('resendCode')
                            ->label(__('auth.resend_code'))
                            ->visible($onCodeStep)
                            ->action('resendCode'),
                    ),

                // Step 3: the Terms in a scroll box; reaching the end sets
                // terms_read (see resources/views/auth/terms-box.blade.php),
                // which unlocks the register button.
                Hidden::make('terms_read')->dehydrated(false),

                Placeholder::make('terms_box')
                    ->label(__('auth.terms_step_label'))
                    ->visible(fn (): bool => $this->step === 3)
                    ->content(fn (): HtmlString => new HtmlString(
                        view('auth.terms-box', [
                            'html' => Str::markdown((string) LegalDocument::bodyFor(LegalDocument::TERMS, app()->getLocale())),
                        ])->render(),
                    )),
            ]);
    }

    /** Step 1 sends the code, step 2 verifies it, step 3 accepts the Terms and creates the account. */
    public function register(): ?RegistrationResponse
    {
        if ($this->step === 1) {
            $this->validateEmailStep();
            $this->sendCode();
            $this->step = 2;

            return null;
        }

        if ($this->step === 2) {
            $this->validateCode();

            // Server-side proof the OTP passed: $step is client-visible Livewire
            // state, so the final step re-checks this session flag instead.
            session(['register_otp_email' => mb_strtolower(trim((string) data_get($this->form->getRawState(), 'email')))]);
            $this->step = 3;

            return null;
        }

        $email = mb_strtolower(trim((string) data_get($this->form->getRawState(), 'email')));
        if (session('register_otp_email') !== $email || $email === '') {
            // Never verified (or the email was swapped afterwards) → start over.
            $this->step = 1;

            throw ValidationException::withMessages(['data.email' => __('auth.code_invalid')]);
        }

        if (! (bool) data_get($this->data, 'terms_read')) {
            Notification::make()->title(__('auth.terms_scroll_hint'))->warning()->send();

            return null;
        }

        session()->forget('register_otp_email');

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
            ->label(fn (): string => match ($this->step) {
                1 => __('auth.continue_with_email'),
                2 => __('auth.create_account'),
                default => __('auth.agree_continue'),
            })
            // Step 3: locked until the Terms were scrolled to the end (the
            // scroll box flips terms_read via $wire.set, re-rendering this).
            ->disabled(fn (): bool => $this->step === 3 && ! (bool) data_get($this->data, 'terms_read'));
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
            // Remember the language they signed up in so the beta/welcome emails
            // and the pending screen match it (defaults to 'en' otherwise).
            'locale' => in_array(app()->getLocale(), ['en', 'de'], true) ? app()->getLocale() : 'en',
        ]);

        // The code proved mailbox ownership — mark verified, same as Google.
        // Step 3 read + accepted the current Terms — stamp the version.
        $user->forceFill([
            'email_verified_at' => now(),
            'terms_version' => LegalDocument::currentVersion(LegalDocument::TERMS),
            'terms_accepted_at' => now(),
        ])->save();

        // Invited sign-up: attach to the inviter's workspace instead of
        // provisioning an empty one of their own. The invitation also vouches
        // for them in the private beta.
        if ($this->attachToPendingInvite($user)) {
            $user->forceFill(['approved_at' => now()])->save();
            $this->sendWelcomeEmail($user);

            return $user;
        }

        $beta = app(BetaAccess::class);

        // Private beta: unknown emails only apply for access. No workspace is
        // provisioned; EnsureBetaApproved shows the pending screen until a
        // super admin activates the account.
        if (! $beta->grantsImmediateAccess($email)) {
            $beta->sendReceivedEmail($user);

            return $user;
        }

        $user->forceFill(['approved_at' => now()])->save();

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
