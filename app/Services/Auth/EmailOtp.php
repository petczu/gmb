<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Mail\SignupCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One-time sign-up codes: a 6-digit code is emailed and must be entered on the
 * registration page, proving email ownership before the account exists (the
 * created user is email-verified right away, same as Google sign-ups). Codes
 * live in the cache for TTL_MINUTES; wrong entries and re-sends are throttled.
 */
class EmailOtp
{
    public const TTL_MINUTES = 10;

    /** Codes sent per email within the TTL window. */
    public const MAX_SENDS = 3;

    /** Wrong entries before the code is invalidated. */
    public const MAX_ATTEMPTS = 5;

    /** @throws TooManyCodeRequests when the address asked too often */
    public function send(string $email, string $locale = 'en'): void
    {
        $limiterKey = 'signup-otp-send:'.$this->keyFor($email);

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS)) {
            throw new TooManyCodeRequests;
        }

        RateLimiter::hit($limiterKey, self::TTL_MINUTES * 60);

        $code = (string) random_int(100000, 999999);

        Cache::put(
            $this->cacheKey($email),
            ['hash' => Hash::make($code), 'attempts' => 0],
            now()->addMinutes(self::TTL_MINUTES),
        );

        Mail::to($email)->send(new SignupCodeMail($code, $locale));
    }

    /** True when the code matches; the code is consumed on success. */
    public function verify(string $email, string $code): bool
    {
        $key = $this->cacheKey($email);
        $entry = Cache::get($key);

        if (! is_array($entry)) {
            return false;
        }

        if (($entry['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return false;
        }

        if (Hash::check($code, (string) ($entry['hash'] ?? ''))) {
            Cache::forget($key);
            RateLimiter::clear('signup-otp-send:'.$this->keyFor($email));

            return true;
        }

        $entry['attempts'] = ($entry['attempts'] ?? 0) + 1;
        Cache::put($key, $entry, now()->addMinutes(self::TTL_MINUTES));

        return false;
    }

    private function cacheKey(string $email): string
    {
        return 'signup-otp:'.$this->keyFor($email);
    }

    private function keyFor(string $email): string
    {
        return sha1(mb_strtolower(trim($email)));
    }
}
