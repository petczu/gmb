<?php

use App\Http\Middleware\ResolveMcpWorkspace;
use App\Mcp\Servers\WorkspaceServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth2.1 discovery + dynamic client registration routes, so an MCP client
// (e.g. the claude.ai connector) only needs the endpoint URL — it registers
// itself and the user authorizes on first request. No manual token to paste.
Mcp::oauthRoutes();

// Per-workspace MCP server. `auth:api` (Passport) authenticates the user from
// the OAuth access token; ResolveMcpWorkspace then verifies membership of the
// {workspace} in the URL, gates the Pro plan and initializes that workspace's
// tenancy so every tool is scoped strictly to its data.
// Endpoint: https://<app>/mcp/{workspace-slug}
Mcp::web('/mcp/{workspace}', WorkspaceServer::class)
    ->middleware(['auth:api', ResolveMcpWorkspace::class, 'throttle:60,1']);
