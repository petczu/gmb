<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Billing\Plans;
use App\Models\McpWorkspaceSelection;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ConvertsPsrResponses;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use Laravel\Passport\Http\Controllers\RetrievesAuthRequestFromSession;
use League\OAuth2\Server\AuthorizationServer;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Approves an MCP OAuth authorization while binding the connection to the
 * workspace the user picked on the consent screen. The stock Passport approve
 * controller can't carry that extra field, so this replaces it for the MCP
 * consent form: it stores the (user, OAuth client) => workspace choice, then
 * completes the authorization itself. ResolveMcpWorkspace reads the choice
 * back on every /mcp request.
 */
class McpAuthorizationController
{
    use ConvertsPsrResponses, HandlesOAuthErrors, RetrievesAuthRequestFromSession;

    public function __construct(
        protected AuthorizationServer $server,
        protected LocationBilling $billing,
    ) {}

    public function approve(Request $request, ResponseInterface $psrResponse): Response
    {
        // Consumes authToken/authRequest from the session (single use).
        $authRequest = $this->getAuthRequestFromSession($request);

        // Checkbox list (several workspaces) with the legacy single-radio
        // field as a fallback for cached consent pages.
        $picked = array_values(array_filter(array_map(
            'strval',
            (array) $request->input('workspace_ids', (array) $request->input('workspace_id', [])),
        )));

        $this->rememberWorkspaces(
            (int) $authRequest->getUser()->getIdentifier(),
            (string) $authRequest->getClient()->getIdentifier(),
            $picked,
        );

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $psrResponse)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }

    /**
     * Persist the picked workspaces (one row each), keeping only real Pro
     * workspaces the user belongs to. Invalid values are dropped; when nothing
     * valid remains the previous binding is kept, and ResolveMcpWorkspace
     * falls back to the user's first MCP-enabled workspace if there is none.
     *
     * @param  array<int, string>  $workspaceIds
     */
    protected function rememberWorkspaces(int $userId, string $clientId, array $workspaceIds): void
    {
        $user = User::find($userId);

        if ($user === null || $workspaceIds === []) {
            return;
        }

        $valid = $user->workspaces()->whereKey($workspaceIds)->get()
            ->filter(fn (Workspace $workspace): bool => $this->billing->allows($workspace, Plans::MCP))
            // Keep the order the user picked them in — the first one becomes
            // the connection's default workspace.
            ->sortBy(fn (Workspace $workspace): int|false => array_search($workspace->getKey(), $workspaceIds, true))
            ->values();

        if ($valid->isEmpty()) {
            return;
        }

        McpWorkspaceSelection::query()
            ->where('user_id', $userId)
            ->where('oauth_client_id', $clientId)
            ->delete();

        foreach ($valid as $workspace) {
            McpWorkspaceSelection::create([
                'user_id' => $userId,
                'oauth_client_id' => $clientId,
                'workspace_id' => $workspace->getKey(),
            ]);
        }
    }
}
