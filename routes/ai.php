<?php

use App\Http\Middleware\ResolveMcpWorkspace;
use App\Mcp\Servers\WorkspaceServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth2.1 discovery + dynamic client registration routes, so an MCP client
// (e.g. the claude.ai connector) only needs the endpoint URL — it registers
// itself and the user authorizes on first request. No manual token to paste.
Mcp::oauthRoutes();

// Single MCP endpoint. `auth:api` (Passport) authenticates the user from the
// OAuth access token; ResolveMcpWorkspace then picks the user's MCP-enabled
// workspace, gates the Pro plan and initializes its tenancy so every tool is
// scoped strictly to that data.
// Endpoint: https://<app>/mcp
Mcp::web('/mcp', WorkspaceServer::class)
    ->middleware(['auth:api', ResolveMcpWorkspace::class, 'throttle:60,1']);
