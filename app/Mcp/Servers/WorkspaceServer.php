<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetReviewTool;
use App\Mcp\Tools\ListLocationsTool;
use App\Mcp\Tools\ListReviewsTool;
use App\Mcp\Tools\ReplyToReviewTool;
use App\Mcp\Tools\ReviewStatsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Contracts\Transport;

#[Version('1.0.0')]
#[Instructions(<<<'TXT'
Read and act on the Google Business Profile reviews for the authenticated
workspace. Every tool is scoped to that one workspace. Use list_locations and
list_reviews to explore, review_stats for aggregates, and get_review for detail.
The reply_to_review tool only appears when the workspace has explicitly enabled
write access in its MCP settings; when present, posting a reply is permanent and
public on Google.
TXT)]
class WorkspaceServer extends Server
{
    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        // The advertised server name follows the configured product name — the
        // brand is never hardcoded (attributes only take constants).
        $this->name = (string) config('app.name');
    }

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ListLocationsTool::class,
        ListReviewsTool::class,
        GetReviewTool::class,
        ReviewStatsTool::class,
        // Registered only when the workspace opted into write access.
        ReplyToReviewTool::class,
    ];

    /** @var array<int, class-string<\Laravel\Mcp\Server\Resource>> */
    protected array $resources = [];

    /** @var array<int, class-string<\Laravel\Mcp\Server\Prompt>> */
    protected array $prompts = [];
}
