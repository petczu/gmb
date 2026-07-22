<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ReplyFailure;
use RuntimeException;
use Tests\TestCase;

class ReplyFailureTest extends TestCase
{
    public function test_humanize_maps_known_http_errors(): void
    {
        $this->assertSame(__('resources/auto_reply.error_not_found'), ReplyFailure::humanize(new RuntimeException('[404] resource not found')));
        $this->assertSame(__('resources/auto_reply.error_rate_limited'), ReplyFailure::humanize(new RuntimeException('429 Too Many Requests')));
        $this->assertSame(__('resources/auto_reply.error_unauthorized'), ReplyFailure::humanize(new RuntimeException('401 unauthorized')));
        $this->assertSame(__('resources/auto_reply.error_generic'), ReplyFailure::humanize(new RuntimeException('connection reset')));
    }

    public function test_transient_failures_are_retryable_but_structural_ones_are_not(): void
    {
        $this->assertTrue(ReplyFailure::isRetryable(__('resources/auto_reply.error_generic')));
        $this->assertTrue(ReplyFailure::isRetryable(__('resources/auto_reply.error_rate_limited')));
        $this->assertTrue(ReplyFailure::isRetryable(null));
        $this->assertTrue(ReplyFailure::isRetryable(''));

        $this->assertFalse(ReplyFailure::isRetryable(__('resources/auto_reply.error_not_found')));
        $this->assertFalse(ReplyFailure::isRetryable(__('resources/auto_reply.error_unauthorized')));
    }
}
