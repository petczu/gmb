<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Workspace;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The dispatcher mirrors each email as an in-app database notification (the
 * panel bell), tagged with the workspace so the bell can be scoped per
 * workspace. No subject → no bell.
 */
class InAppNotificationTest extends TestCase
{
    private function workspace(): Workspace
    {
        $workspace = new Workspace;
        $workspace->id = 'ws-test';

        return $workspace;
    }

    private function mailableWithSubject(string $subject): Mailable
    {
        return new class($subject) extends Mailable
        {
            public function __construct(private string $subjectLine) {}

            public function envelope(): Envelope
            {
                return new Envelope(subject: $this->subjectLine);
            }
        };
    }

    /** @return array<string, mixed>|null */
    private function bellData(Mailable $mailable): ?array
    {
        $dispatcher = app(NotificationDispatcher::class);
        $method = new ReflectionMethod($dispatcher, 'bellData');

        return $method->invoke($dispatcher, $mailable, NotificationCategory::OPERATIONS, $this->workspace());
    }

    public function test_bell_payload_is_tagged_with_the_workspace(): void
    {
        $data = $this->bellData($this->mailableWithSubject('You have a new review'));

        $this->assertIsArray($data);
        $this->assertSame('You have a new review', $data['title']);
        $this->assertSame('ws-test', $data['workspace_id']);
        $this->assertSame('filament', $data['format']);
    }

    public function test_no_bell_payload_without_a_subject(): void
    {
        $this->assertNull($this->bellData($this->mailableWithSubject('')));
    }
}
