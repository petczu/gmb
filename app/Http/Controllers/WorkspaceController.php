<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Workspaces\WorkspaceProvisioner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a signed-in user spin up an additional workspace from the switcher.
 * Provisions a fresh tenant (DB + roles), makes them its owner and switches to
 * it; the onboarding guide then walks them through setup.
 */
class WorkspaceController extends Controller
{
    public function create(): View
    {
        return view('workspace.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $workspace = app(WorkspaceProvisioner::class)->create($request->user(), $data['name']);

        session(['current_workspace_id' => $workspace->id]);

        return redirect('/');
    }
}
