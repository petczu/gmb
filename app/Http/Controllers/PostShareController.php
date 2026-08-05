<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PostShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * PUBLIC shared post links (no login), the same flow as shared reports: the
 * snapshot HTML is stored on the share row; optional password gate and access
 * window. The password/unavailable/not-found screens are shared with reports.
 */
class PostShareController extends Controller
{
    public function shared(Request $request, string $token): Response
    {
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

        return response($share->html);
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

    /**
     * Session unlock key, tied to the CURRENT password hash: changing or
     * removing the password re-prompts previously unlocked sessions.
     */
    private function unlockKey(PostShare $share): string
    {
        return 'post_share:'.$share->token.':'.md5((string) $share->password);
    }
}
