<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Mail\TemplatedMailable;
use App\Models\User;
use App\Models\Workspace;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ReflectionMethod;
use Throwable;

/**
 * Sends a notification to every configured recipient of a category. The builder
 * closure receives each recipient's name and locale and returns the localized
 * Mailable, so a single call fans out one personalized email per recipient.
 */
class NotificationDispatcher
{
    public function __construct(private NotificationRecipients $recipients) {}

    /**
     * @param  Closure(string $name, string $lang): Mailable  $build
     * @param  ?int  $locationId  restrict to recipients covering this location (null = all)
     */
    public function dispatch(Workspace $workspace, string $category, Closure $build, ?int $locationId = null): void
    {
        foreach ($this->recipients->for($workspace, $category, $locationId) as $user) {
            $lang = $user->locale ?? 'en';
            $isGuest = ($user->pivot->membership_type ?? null) === 'guest';

            try {
                $mailable = $build($user->name, $lang);

                // Guests have no login — drop the app CTA button so the email
                // doesn't dead-end them on the sign-in page.
                if ($mailable instanceof TemplatedMailable && $isGuest) {
                    $mailable->withoutCta();
                }

                Mail::to($user->email)->send($mailable);
            } catch (Throwable $e) {
                Log::warning('Notification dispatch failed', [
                    'workspace' => $workspace->id,
                    'category' => $category,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            // In-app bell for members who can actually sign in (guests can't).
            if (! $isGuest) {
                $this->toDatabase($user, $mailable);
            }
        }
    }

    /**
     * Mirror the email as an in-app database notification (the panel bell),
     * reusing the mailable's subject as the title and its CTA url as the link.
     * Best-effort: a bell failure must never break the (already sent) email.
     */
    private function toDatabase(User $user, Mailable $mailable): void
    {
        try {
            $title = trim((string) $mailable->envelope()->subject);
            if ($title === '') {
                return;
            }

            $notification = Notification::make()->title($title);

            $url = $this->mailableUrl($mailable);
            if ($url !== null) {
                $notification->actions([
                    Action::make('open')->url($url)->markAsRead(),
                ]);
            }

            $notification->sendToDatabase($user);
        } catch (Throwable $e) {
            Log::warning('In-app notification failed', [
                'user' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** The CTA url from a templated mailable's placeholder data, if any. */
    private function mailableUrl(Mailable $mailable): ?string
    {
        if (! $mailable instanceof TemplatedMailable) {
            return null;
        }

        try {
            $method = new ReflectionMethod($mailable, 'templateData');
            $method->setAccessible(true);
            /** @var array<string, mixed> $data */
            $data = $method->invoke($mailable);

            return filled($data['url'] ?? null) ? (string) $data['url'] : null;
        } catch (Throwable) {
            return null;
        }
    }
}
