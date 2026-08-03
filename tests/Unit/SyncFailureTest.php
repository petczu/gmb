<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SyncFailure;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SyncFailureTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function transientCases(): array
    {
        return [
            'not found' => ['404 location not found', true],
            'curl timeout' => ['cURL error 28: Operation timed out after 30002 milliseconds', true],
            'generic timeout' => ['Operation timeout', true],
            'connection reset' => ['Connection reset by peer', true],
            'could not resolve' => ['Could not resolve host: zernio.com', true],
            'auth error' => ['401 Unauthorized: token expired', false],
            'server error' => ['500 Internal Server Error', false],
            'generic' => ['something unexpected went wrong', false],
        ];
    }

    #[DataProvider('transientCases')]
    public function test_it_classifies_transient_failures(string $message, bool $expected): void
    {
        $this->assertSame($expected, SyncFailure::isTransient($message));
    }

    public function test_timeout_maps_to_the_timeout_reason(): void
    {
        // humanize() returns a translation key; the timeout bucket differs from
        // the generic one, proving the timeout branch is hit.
        $this->assertNotSame(
            SyncFailure::humanize('all quiet'),
            SyncFailure::humanize('cURL error 28: Operation timed out'),
        );
    }
}
