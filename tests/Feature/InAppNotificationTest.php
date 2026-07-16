<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Notification;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The dispatcher mirrors each email as an in-app database notification (the
 * panel bell) for members who can sign in, using the mailable's subject as the
 * title. Faked so it doesn't depend on the central notifications table (the
 * queued Filament notification restores the User on its own connection).
 */
class InAppNotificationTest extends TestCase
{
    private function member(): User
    {
        $user = new User;
        $user->forceFill(['id' => 1]);
        $user->exists = true;

        return $user;
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

    private function invokeToDatabase(User $user, Mailable $mailable): void
    {
        $dispatcher = app(NotificationDispatcher::class);
        $method = new ReflectionMethod($dispatcher, 'toDatabase');
        $method->invoke($dispatcher, $user, $mailable);
    }

    public function test_it_sends_a_bell_notification_for_the_member(): void
    {
        Notification::fake();

        $this->invokeToDatabase($this->member(), $this->mailableWithSubject('You have a new review'));

        Notification::assertSentTo($this->member(), DatabaseNotification::class);
    }

    public function test_it_skips_notifications_without_a_subject(): void
    {
        Notification::fake();

        $this->invokeToDatabase($this->member(), $this->mailableWithSubject(''));

        Notification::assertNothingSent();
    }
}
