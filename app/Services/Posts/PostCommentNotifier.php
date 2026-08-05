<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Mail\PostMentionMail;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Notifies the workspace members @-mentioned in a post comment: an in-app bell
 * entry (scoped to the workspace) now, and an email (added next). Best-effort:
 * a notification failure must never break posting the comment. The comment
 * author is never notified about their own mention.
 */
class PostCommentNotifier
{
    public function notifyMentioned(Post $post, PostComment $comment): void
    {
        $workspace = tenant();
        if (! $workspace instanceof Workspace) {
            return;
        }

        $mentionedIds = array_values(array_filter(
            array_map('intval', $comment->mentioned_user_ids ?? []),
            fn (int $id): bool => $id !== (int) $comment->user_id,
        ));

        if ($mentionedIds === []) {
            return;
        }

        $author = (string) ($comment->user_name ?? __('pages/posts.activity_system'));
        $url = rtrim((string) config('app.url'), '/').'/posts';
        // Escaped: the excerpt lands in the email's HTML body.
        $excerpt = e(Str::limit((string) $comment->body, 300));

        foreach (User::query()->whereIn('id', $mentionedIds)->get() as $user) {
            $this->toDatabase($user, $workspace, $author, $post, $url);
            $this->toEmail($user, $author, $excerpt, $url);
        }
    }

    /** Email the mentioned member in their own language. Best-effort. */
    private function toEmail(User $user, string $author, string $excerpt, string $url): void
    {
        if (blank($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new PostMentionMail(
                mentionerName: $author,
                excerpt: $excerpt,
                postsUrl: $url,
                lang: $user->locale ?? 'en',
            ));
        } catch (Throwable $e) {
            Log::warning('Post mention email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    /** Mirror the mention as an in-app database notification (the panel bell). */
    private function toDatabase(User $user, Workspace $workspace, string $author, Post $post, string $url): void
    {
        try {
            $notification = Notification::make()
                ->title(__('pages/posts.comment_mention_title', ['name' => $author]))
                ->body(Str::limit((string) $post->title ?: (string) $post->caption, 80))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->iconColor('info')
                ->actions([Action::make('open')->url($url)->markAsRead()]);

            $data = $notification->getDatabaseMessage();
            $data['workspace_id'] = (string) $workspace->id;

            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => FilamentDatabaseNotification::class,
                'data' => $data,
                'read_at' => null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Post mention notification failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
