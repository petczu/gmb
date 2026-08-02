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

        $this->rememberWorkspace(
            (int) $authRequest->getUser()->getIdentifier(),
            (string) $authRequest->getClient()->getIdentifier(),
            (string) $request->input('workspace_id', ''),
        );

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $psrResponse)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }

    /**
     * Persist the picked workspace when it is a real Pro workspace the user
     * belongs to. An invalid or missing value is ignored: ResolveMcpWorkspace
     * then falls back to the user's first MCP-enabled workspace.
     */
    protected function rememberWorkspace(int $userId, string $clientId, string $workspaceId): void
    {
        if ($workspaceId === '') {
            return;
        }

        $user = User::find($userId);

        if ($user === null) {
            return;
        }

        $workspace = $user->workspaces()->whereKey($workspaceId)->first();

        if (! $workspace instanceof Workspace || ! $this->billing->allows($workspace, Plans::MCP)) {
            return;
        }

        McpWorkspaceSelection::updateOrCreate(
            ['user_id' => $userId, 'oauth_client_id' => $clientId],
            ['workspace_id' => $workspace->getKey()],
        );
    }
}
