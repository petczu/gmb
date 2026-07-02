<?php

declare(strict_types=1);

namespace App\Services\Ai\Data;

final readonly class GeneratedReply
{
    public function __construct(
        public string $text,
        public string $model,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}
}
