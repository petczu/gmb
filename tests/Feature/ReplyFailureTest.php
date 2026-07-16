<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\ReplyFailure;
use RuntimeException;
use Tests\TestCase;

/**
 * Raw provider errors are mapped to short, human-readable reasons for the
 * Approvals "Failed" tab (the raw text stays in the logs).
 */
class ReplyFailureTest extends TestCase
{
    public function test_maps_a_404_to_the_not_found_reason(): void
    {
        $error = new RuntimeException('[404] Client error: `POST https://zernio.com/api/v1/.../reply` resulted in a `404 Not Found` response: {"error":"The requested Google Business Profile resource was not found."}');

        $this->assertSame(__('resources/auto_reply.error_not_found'), ReplyFailure::humanize($error));
    }

    public function test_maps_a_rate_limit_to_its_reason(): void
    {
        $this->assertSame(__('resources/auto_reply.error_rate_limited'), ReplyFailure::humanize('[429] rate limit exceeded'));
    }

    public function test_unknown_errors_fall_back_to_generic(): void
    {
        $this->assertSame(__('resources/auto_reply.error_generic'), ReplyFailure::humanize('some unexpected 500 server error'));
    }
}
