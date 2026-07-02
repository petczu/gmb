<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Computes the USD cost of an AI call from token counts and the model's list
 * price (config services.ai.pricing, per 1,000,000 tokens).
 */
class AiCost
{
    public static function usd(?string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = (array) config('services.ai.pricing', []);
        $rates = $pricing[$model] ?? $pricing['default'] ?? ['input' => 0.0, 'output' => 0.0];

        $cost = ($inputTokens / 1_000_000) * (float) ($rates['input'] ?? 0)
            + ($outputTokens / 1_000_000) * (float) ($rates['output'] ?? 0);

        return round($cost, 6);
    }
}
