<?php

declare(strict_types=1);

namespace App\Mcp\Tools\Concerns;

use App\Models\Workspace;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lets a tool operate on any of the workspaces this MCP connection was
 * authorized for (consent-screen checkboxes). The middleware initializes the
 * first allowed workspace as the default and shares the full set via the
 * `mcp.allowed_workspaces` container binding; a tool call may pass an optional
 * `workspace` argument (id or exact name) to switch tenancy for that call.
 */
trait ResolvesRequestedWorkspace
{
    /** @return Collection<int, Workspace> */
    protected function allowedWorkspaces(): Collection
    {
        return app()->bound('mcp.allowed_workspaces') ? app('mcp.allowed_workspaces') : collect();
    }

    /**
     * The `workspace` argument schema, only when there is a real choice.
     *
     * @return array<string, JsonSchema>
     */
    protected function workspaceSchema(JsonSchema $schema): array
    {
        if ($this->allowedWorkspaces()->count() < 2) {
            return [];
        }

        return [
            'workspace' => $schema->string()->description(
                'Workspace to operate on (name or id). Optional; defaults to '
                .'the connection\'s first authorized workspace. Authorized: '
                .$this->allowedWorkspaces()->map(fn (Workspace $w): string => $w->name)->implode(', ').'.'
            ),
        ];
    }

    /**
     * Switch tenancy to the requested workspace. Null when the call may
     * proceed (no argument, or switched successfully); an error Response when
     * the requested workspace is not part of this connection.
     */
    protected function switchWorkspace(Request $request): ?Response
    {
        $requested = trim((string) $request->get('workspace', ''));

        if ($requested === '') {
            return null;
        }

        $workspace = $this->allowedWorkspaces()->first(
            fn (Workspace $w): bool => $w->getKey() === $requested || strcasecmp((string) $w->name, $requested) === 0,
        );

        if ($workspace === null) {
            return Response::error(
                'Unknown workspace "'.$requested.'". This connection is authorized for: '
                .$this->allowedWorkspaces()->map(fn (Workspace $w): string => $w->name)->implode(', ')
                .'. Re-authorize to add more.'
            );
        }

        tenancy()->initialize($workspace);
        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->getKey());

        return null;
    }
}
