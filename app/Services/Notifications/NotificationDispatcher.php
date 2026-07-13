<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Mail\TemplatedMailable;
use App\Models\Workspace;
use Closure;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

            try {
                $mailable = $build($user->name, $lang);

                // Guests have no login — drop the app CTA button so the email
                // doesn't dead-end them on the sign-in page.
                if ($mailable instanceof TemplatedMailable
                    && ($user->pivot->membership_type ?? null) === 'guest') {
                    $mailable->withoutCta();
                }

                Mail::to($user->email)->send($mailable);
            } catch (Throwable $e) {
                Log::warning('Notification dispatch failed', [
                    'workspace' => $workspace->id,
                    'category' => $category,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
