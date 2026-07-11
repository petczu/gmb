<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Runaway-loop backstop for interactive AI generations (agent tests,
 * description drafts, Ask AI, report helpers) — not a meter for humans. One
 * generous hourly cap per user, configurable via AI_UI_GENERATION_RATE_LIMIT
 * (0 disables). Real spend control lives in the plan allowances and credits.
 */
class AiRateLimit
{
    /** Returns true when the user is over the limit; otherwise records the attempt. */
    public static function hit(string $action): bool
    {
        $limit = (int) config('services.ai.ui_generation_rate_limit', 100);
        if ($limit <= 0) {
            return false;
        }

        $key = $action.':'.(auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, maxAttempts: $limit)) {
            return true;
        }

        RateLimiter::hit($key, 3600);

        return false;
    }
}
