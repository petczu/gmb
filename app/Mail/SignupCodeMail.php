<?php

declare(strict_types=1);

namespace App\Mail;

use App\Services\Auth\EmailOtp;

class SignupCodeMail extends TemplatedMailable
{
    public function __construct(public string $code, public string $lang = 'en')
    {
        $this->locale($lang);
    }

    protected function templateKey(): string
    {
        return 'signup_code';
    }

    protected function templateData(): array
    {
        return ['code' => $this->code, 'minutes' => EmailOtp::TTL_MINUTES];
    }
}
