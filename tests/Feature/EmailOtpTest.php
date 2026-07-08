<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\SignupCodeMail;
use App\Services\Auth\EmailOtp;
use App\Services\Auth\TooManyCodeRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Sign-up codes: emailed 6-digit codes prove mailbox ownership before the
 * account exists. Codes expire, are consumed on success, tolerate only a few
 * wrong entries and re-sends are throttled per address.
 */
class EmailOtpTest extends TestCase
{
    private function sendAndCapture(EmailOtp $otp, string $email): string
    {
        $code = null;
        Mail::fake();
        $otp->send($email);
        Mail::assertSent(SignupCodeMail::class, function (SignupCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        return (string) $code;
    }

    public function test_code_is_sent_verified_and_consumed(): void
    {
        $otp = app(EmailOtp::class);
        $code = $this->sendAndCapture($otp, 'otp-test@example.com');

        $this->assertSame(6, strlen($code));
        $this->assertFalse($otp->verify('otp-test@example.com', '000000'));
        $this->assertTrue($otp->verify('otp-test@example.com', $code));

        // Consumed: the same code doesn't work twice.
        $this->assertFalse($otp->verify('otp-test@example.com', $code));
    }

    public function test_too_many_wrong_entries_invalidate_the_code(): void
    {
        $otp = app(EmailOtp::class);
        $code = $this->sendAndCapture($otp, 'otp-brute@example.com');

        for ($i = 0; $i < EmailOtp::MAX_ATTEMPTS; $i++) {
            $this->assertFalse($otp->verify('otp-brute@example.com', '111111'));
        }

        // Even the right code is dead now.
        $this->assertFalse($otp->verify('otp-brute@example.com', $code));
    }

    public function test_resends_are_throttled_per_address(): void
    {
        $otp = app(EmailOtp::class);
        Mail::fake();

        for ($i = 0; $i < EmailOtp::MAX_SENDS; $i++) {
            $otp->send('otp-throttle@example.com');
        }

        $this->expectException(TooManyCodeRequests::class);
        $otp->send('otp-throttle@example.com');
    }
}
