<?php

declare(strict_types=1);

namespace App\Filament\App\Auth;

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
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
