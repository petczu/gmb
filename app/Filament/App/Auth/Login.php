<?php

declare(strict_types=1);

namespace App\Filament\App\Auth;

use App\Services\Workspaces\InvitationAcceptor;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Schema;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * Login page with the "Forgot password?" link moved onto the "Remember me"
 * row (right-aligned) instead of sitting next to the password field label.
 *
 * When the visitor arrived from an invitation link (the invited address
 * already has an account, so the invite page sent them here), a banner says
 * which workspace they are joining and with which (masked) address —
 * otherwise the redirect to a bare login form reads as "nothing happened".
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('invite_banner')
                    ->hiddenLabel()
                    ->visible(fn (): bool => app(InvitationAcceptor::class)->pendingFromSession() !== null)
                    ->content(function (): HtmlString {
                        $invitation = app(InvitationAcceptor::class)->pendingFromSession();
                        $masked = InvitationAcceptor::maskEmail((string) $invitation?->email);

                        return new HtmlString(view('auth.invite-banner', [
                            'workspace' => (string) ($invitation?->workspace?->name ?? ''),
                            'email' => $masked,
                            'hint' => __('auth.invite_email_login', ['email' => $masked]),
                        ])->render());
                    }),

                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                Flex::make([
                    // The checkbox grows to fill the row, pushing the reset link
                    // hard against the right edge.
                    $this->getRememberFormComponent()->grow(),
                    $this->getForgotPasswordFormComponent()->grow(false),
                ])->verticalAlignment(VerticalAlignment::Center),
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        // Drop the default hint link: the reset link now lives on the row below.
        return parent::getPasswordFormComponent()->hint(null);
    }

    /** Right-aligned "Forgot password?" link, shown only when reset is enabled. */
    protected function getForgotPasswordFormComponent(): Component
    {
        return Placeholder::make('forgotPassword')
            ->hiddenLabel()
            ->visible(filament()->hasPasswordReset())
            ->content(new HtmlString(Blade::render(
                '<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="-1">'
                .'{{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}'
                .'</x-filament::link>'
            )));
    }
}
