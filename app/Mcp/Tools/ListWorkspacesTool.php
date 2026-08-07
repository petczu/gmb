<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ResolvesRequestedWorkspace;
use App\Models\Workspace;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the workspaces this connection is authorized for. Other tools accept an optional `workspace` argument (name or id) to operate on a specific one; without it they use the first listed workspace.')]
class ListWorkspacesTool extends Tool
{
    use ResolvesRequestedWorkspace;

    public function handle(Request $request): Response
    {
        $workspaces = $this->allowedWorkspaces()->map(fn (Workspace $workspace, int $index): array => [
            'id' => (string) $workspace->getKey(),
            'name' => (string) $workspace->name,
            'default' => $index === 0,
        ])->values()->all();

        return Response::text((string) json_encode([
            'count' => count($workspaces),
            'workspaces' => $workspaces,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
