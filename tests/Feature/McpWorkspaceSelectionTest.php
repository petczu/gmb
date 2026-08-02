<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Billing\Plans;
use App\Http\Middleware\ResolveMcpWorkspace;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Covers how an MCP request picks its workspace for the single /mcp endpoint:
 * the bound (consent-time) choice with a fall back to the user's first
 * MCP-enabled workspace. The decision is tested in isolation so no stancl
 * tenancy bootstrap or central DB is needed. The consent-form wiring is
 * covered by the view render test; route resolution by OauthRouteResolutionTest.
 */
class McpWorkspaceSelectionTest extends TestCase
{
    /**
     * @param  array<int, string>  $proIds  workspace ids the mock treats as MCP-enabled
     */
    private function billingAllowing(array $proIds): LocationBilling
    {
        $billing = $this->createMock(LocationBilling::class);
        $billing->method('allows')->willReturnCallback(
            fn (Workspace $workspace, string $feature): bool => $feature === Plans::MCP && in_array($workspace->id, $proIds, true),
        );

        return $billing;
    }

    /**
     * @param  array<int, string>  $ids
     * @return Collection<int, Workspace>
     */
    private function workspaces(array $ids): Collection
    {
        return collect($ids)->map(fn (string $id): Workspace => tap(new Workspace, fn (Workspace $w) => $w->id = $id));
    }

    public function test_resolve_prefers_the_bound_workspace_when_it_is_pro(): void
    {
        $chosen = (new ResolveMcpWorkspace)->resolveWorkspace(
            $this->workspaces(['a', 'b', 'c']),
            'b',
            $this->billingAllowing(['a', 'b', 'c']),
        );

        $this->assertSame('b', $chosen?->id);
    }

    public function test_resolve_falls_back_to_first_pro_when_bound_is_not_pro(): void
    {
        // 'a' is bound but not Pro; 'b' is the first Pro one.
        $chosen = (new ResolveMcpWorkspace)->resolveWorkspace(
            $this->workspaces(['a', 'b', 'c']),
            'a',
            $this->billingAllowing(['b', 'c']),
        );

        $this->assertSame('b', $chosen?->id);
    }

    public function test_resolve_falls_back_to_first_pro_when_nothing_is_bound(): void
    {
        $chosen = (new ResolveMcpWorkspace)->resolveWorkspace(
            $this->workspaces(['a', 'b']),
            null,
            $this->billingAllowing(['a', 'b']),
        );

        $this->assertSame('a', $chosen?->id);
    }

    public function test_resolve_ignores_a_bound_id_the_user_no_longer_belongs_to(): void
    {
        $chosen = (new ResolveMcpWorkspace)->resolveWorkspace(
            $this->workspaces(['a', 'b']),
            'gone',
            $this->billingAllowing(['a', 'b']),
        );

        $this->assertSame('a', $chosen?->id);
    }

    public function test_resolve_returns_null_when_no_workspace_is_pro(): void
    {
        $chosen = (new ResolveMcpWorkspace)->resolveWorkspace(
            $this->workspaces(['a', 'b']),
            'a',
            $this->billingAllowing([]),
        );

        $this->assertNull($chosen);
    }

    public function test_consent_screen_shows_a_workspace_picker_only_with_several_workspaces(): void
    {
        $mk = fn (string $id, string $name): object => new class($id, $name)
        {
            public function __construct(public string $id, public string $name) {}

            public function getKey(): string
            {
                return $this->id;
            }
        };

        $params = fn (Collection $workspaces): array => [
            'client' => (object) ['id' => 'client-1', 'name' => 'Claude'],
            'user' => (object) ['email' => 'p@example.com'],
            'scopes' => [(object) ['description' => 'Use MCP server']],
            'authToken' => 'tok',
            'workspaces' => $workspaces,
            'selectedWorkspaceId' => $workspaces->first()?->getKey(),
        ];

        // Several workspaces: radio picker, and the form posts to our approve route.
        $multi = view('mcp.authorize', $params(collect([$mk('a', 'Acme'), $mk('b', 'Bistro')])))->render();
        $this->assertStringContainsString('name="workspace_id"', $multi);
        $this->assertStringContainsString(route('mcp.oauth.approve'), $multi);
        $this->assertSame(2, substr_count($multi, 'type="radio"'));

        // One workspace: no picker, but the single id is bound via a hidden field.
        $single = view('mcp.authorize', $params(collect([$mk('a', 'Acme')])))->render();
        $this->assertStringNotContainsString('type="radio"', $single);
        $this->assertStringContainsString('type="hidden" name="workspace_id" value="a"', $single);
    }
}
