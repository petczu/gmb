<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switches the active workspace for a multi-workspace member. Only writes the
 * session pointer when the user actually belongs to the target workspace; the
 * SetCurrentWorkspace middleware initializes the matching tenant on the next
 * request.
 */
class WorkspaceSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $workspaceId = (string) $request->input('workspace');
        $user = $request->user();

        if ($user !== null && $user->workspaces()->whereKey($workspaceId)->exists()) {
            session(['current_workspace_id' => $workspaceId]);
        }

        return redirect('/');
    }
}
