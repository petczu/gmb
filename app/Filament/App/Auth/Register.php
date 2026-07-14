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
use App\Services\Workspaces\InvitationAcceptor;
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
     * When arriving from an invitation link, sign-up is bound to the invited
     * address: the email field is prefilled + locked so the account that gets
     * created is the one the workspace owner invited. Otherwise a mismatched
     * email would silently spin up a fresh solo workspace instead of joining.
     *
     * MASKED (p***@gmail.com): Livewire public state and the form are client-
     * visible, and whoever holds the link is not necessarily the invitee — the
     * full address must never leave the server. The real one is resolved
     * per-request from the session token (see effectiveEmail()).
     */
    public ?string $invitedEmail = null;

    public ?string $invitedWorkspaceName = null;

    /**
     * Deep link from the marketing site's pricing cards:
     * /register?plan=pro&interval=year. The choice is parked in the session so
     * it survives the OTP steps (and social login) and preselects the plan step
     * of the onboarding wizard.
     */
    public function mount(): void
    {
        parent::mount();

        // Invitation-bound sign-up: lock the form to the invited address
        // (displayed masked; the real one never leaves the server).
        $invitation = $this->pendingInvitation();
        if ($invitation !== null) {
            $this->invitedEmail = InvitationAcceptor::maskEmail((string) $invitation->email);
            $this->invitedWorkspaceName = (string) ($invitation->workspace?->name ?? '');
            $this->data['email'] = $this->invitedEmail;
        }

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
                // Invitation banner: explains the locked email up front.
                Placeholder::make('invite_banner')
                    ->hiddenLabel()
                    ->visible(fn (): bool => $this->invitedEmail !== null)
                    ->content(fn (): HtmlString => new HtmlString(
                        view('auth.invite-banner', [
                            'workspace' => $this->invitedWorkspaceName,
                            'email' => (string) $this->invitedEmail,
                        ])->render(),
                    )),

                $this->getEmailFormComponent()
                    // Locked once past step 1, and always when invitation-bound.
                    ->readOnly(fn (): bool => $this->step >= 2 || $this->invitedEmail !== null)
                    ->hintAction(
                        Action::make('changeEmail')
                            ->label(__('auth.change_email'))
                            ->visible(fn (): bool => $this->step >= 2 && $this->invitedEmail === null)
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
        // Invitation-bound sign-up: pin the visible field to the masked form.
        // The REAL address is resolved server-side from the (untamperable)
        // session token in effectiveEmail(), so a crafted client request can't
        // swap in a different address — and the full one never reaches the client.
        $invitation = $this->pendingInvitation();
        if ($invitation !== null) {
            $this->data['email'] = InvitationAcceptor::maskEmail((string) $invitation->email);
        }

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
            session(['register_otp_email' => $this->effectiveEmail()]);
            $this->step = 3;

            return null;
        }

        $email = $this->effectiveEmail();
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

    /**
     * The address this registration is really for: the invited one when the
     * sign-up is invitation-bound (resolved server-side from the session token,
     * because the form only carries the masked display value), otherwise
     * whatever was typed into the form.
     */
    protected function effectiveEmail(): string
    {
        $invited = $this->pendingInvitation()?->email;

        return mb_strtolower(trim($invited ?? (string) data_get($this->form->getRawState(), 'email')));
    }

    /** Early email validation so step 1 fails fast on typos/taken addresses. */
    protected function validateEmailStep(): void
    {
        $email = $this->effectiveEmail();

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
        $email = $this->effectiveEmail();

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
        $email = $this->effectiveEmail();
        $code = (string) data_get($this->form->getRawState(), 'code');

        if (! app(EmailOtp::class)->verify($email, $code)) {
            throw ValidationException::withMessages([
                'data.code' => __('auth.code_invalid'),
            ]);
        }
    }

    protected function handleRegistration(array $data): Model
    {
        // Invitation-bound: the form only carries the masked display value —
        // the account must be created with the real invited address.
        $email = $this->effectiveEmail();

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
     * The pending invitation for this sign-up, resolved fresh from the session
     * token (server-side, so it can't be tampered via Livewire state). Null when
     * there is no token, or it no longer points at a pending invitation.
     */
    protected function pendingInvitation(): ?Invitation
    {
        $token = session('pending_invite');
        if (! is_string($token) || $token === '') {
            return null;
        }

        $invitation = Invitation::query()->where('token', $token)->first();

        return ($invitation !== null && $invitation->isPending()) ? $invitation : null;
    }

    /**
     * If a valid pending invitation matches this user's email, attach them to
     * that workspace via the shared InvitationAcceptor (role, location scope,
     * locale, beta approval). Returns true when handled (so no new workspace
     * is provisioned).
     */
    protected function attachToPendingInvite(Model $user): bool
    {
        $invitation = $this->pendingInvitation();
        if ($invitation === null) {
            return false;
        }

        $accepted = app(InvitationAcceptor::class)->accept($user, $invitation);
        session()->forget('pending_invite');

        return $accepted;
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
