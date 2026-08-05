<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PostComment;
use App\Models\PostShare;
use App\Models\Workspace;
use App\Services\Reports\ReportBranding;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * PUBLIC shared post links (no login), the same gate flow as shared reports
 * (optional password + access window). The page wraps the snapshotted card
 * with the workspace's branding and a guest comment thread: visitors leave
 * their name once, then can comment into the post's feedback thread.
 */
class PostShareController extends Controller
{
    public function shared(Request $request, string $token): Response
    {
        $this->applyLocale($request);

        $share = PostShare::query()->where('token', $token)->first();

        if ($share === null) {
            return response()->view('reports.share-not-found', [], 404);
        }

        if (! $share->withinWindow()) {
            return response()->view('reports.share-unavailable', [], 403);
        }

        if ($share->hasPassword() && ! $request->session()->get($this->unlockKey($share))) {
            return response()->view('reports.share-password', ['token' => $token, 'error' => null, 'action' => route('posts.shared.unlock', $token)]);
        }

        // Legacy rows snapshotted a full HTML document; serve them as-is.
        if (str_starts_with(ltrim($share->html), '<!DOCTYPE')) {
            return response($share->html);
        }

        $comments = $this->inWorkspace($share, fn (): array => PostComment::query()
            ->where('post_id', $share->post_id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (PostComment $c): array => [
                'name' => (string) ($c->user_name ?? '—'),
                'when' => (string) $c->created_at?->diffForHumans(),
                'body' => (string) $c->body,
                'reply' => $c->parent_id !== null,
            ])
            ->all());

        return response()->view('posts.shared', [
            'share' => $share,
            // Post share pages always carry the Repunio brand; workspace
            // white-labelling applies to performance reports only.
            'branding' => ReportBranding::for(null),
            'comments' => $comments,
            // Comments stay hidden when the workspace can't be reached.
            'canComment' => $comments !== null,
            'guestName' => (string) $request->session()->get($this->guestNameKey($share), ''),
            'error' => $request->session()->get('shared_post_error'),
        ]);
    }

    /** PUBLIC: verify the share password and unlock for this session. */
    public function sharedUnlock(Request $request, string $token): RedirectResponse|Response
    {
        $share = PostShare::query()->where('token', $token)->firstOrFail();

        if ($share->hasPassword() && Hash::check((string) $request->input('password'), $share->password)) {
            $request->session()->put($this->unlockKey($share), true);

            return redirect()->route('posts.shared', $token);
        }

        return response()->view('reports.share-password', ['token' => $token, 'error' => 'Incorrect password.', 'action' => route('posts.shared.unlock', $token)], 401);
    }

    /** PUBLIC: a guest comment — name once, then straight into the thread. */
    public function comment(Request $request, string $token): RedirectResponse
    {
        $share = PostShare::query()->where('token', $token)->firstOrFail();

        // The same gates as viewing: no commenting outside the window or
        // behind a locked password.
        abort_unless($share->withinWindow(), 403);
        abort_if($share->hasPassword() && ! $request->session()->get($this->unlockKey($share)), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $request->session()->put($this->guestNameKey($share), $data['name']);

        $created = $this->inWorkspace($share, function () use ($share, $data): bool {
            PostComment::create([
                'post_id' => $share->post_id,
                'user_id' => null,
                'user_name' => $data['name'],
                'body' => $data['body'],
                'attachments' => [],
                'mentioned_user_ids' => [],
            ]);

            return true;
        });

        if ($created === null) {
            $request->session()->flash('shared_post_error', __('shared.unavailable'));
        }

        return redirect()->route('posts.shared', $token);
    }

    /**
     * Run inside the share's workspace tenancy; null when it can't be
     * initialized (deleted workspace, or a test env without tenancy).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T|null
     */
    private function inWorkspace(PostShare $share, callable $callback): mixed
    {
        $workspace = $this->findWorkspace($share->workspace_id);
        if ($workspace === null) {
            return null;
        }

        $previous = tenancy()->initialized ? tenant() : null;

        try {
            tenancy()->initialize($workspace);

            return $callback();
        } catch (Throwable) {
            return null;
        } finally {
            try {
                $previous instanceof Workspace ? tenancy()->initialize($previous) : tenancy()->end();
            } catch (Throwable) {
                // Public request: leaving tenancy uninitialized is fine.
            }
        }
    }

    /**
     * Session unlock key, tied to the CURRENT password hash: changing or
     * removing the password re-prompts previously unlocked sessions.
     */
    private function unlockKey(PostShare $share): string
    {
        return 'post_share:'.$share->token.':'.md5((string) $share->password);
    }

    /** Where the guest's name is remembered for this share. */
    private function guestNameKey(PostShare $share): string
    {
        return 'post_share_guest:'.$share->token;
    }

    /** The share's workspace, or null when central tables are unreachable. */
    private function findWorkspace(string $id): ?Workspace
    {
        try {
            return Workspace::find($id);
        } catch (Throwable) {
            return null;
        }
    }

    /** Guest-picked page language (?lang=xx), remembered for the session. */
    private function applyLocale(Request $request): void
    {
        $requested = (string) $request->query('lang', '');

        if (in_array($requested, Locales::codes(), true)) {
            $request->session()->put('shared_page_locale', $requested);
        }

        $locale = (string) $request->session()->get('shared_page_locale', '');
        if (in_array($locale, Locales::codes(), true)) {
            app()->setLocale($locale);
        }
    }
}
