<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Workspace;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationRecipients;
use Mockery;
use Tests\TestCase;

/**
 * Covers the routing-map parsing (array, JSON string, unset) in isolation. The
 * DB-backed recipient resolution and the dispatcher fan-out are verified live
 * against the real central DB, which the test env does not provision.
 */
class NotificationRecipientsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function workspaceWithRoutes(mixed $value): Workspace
    {
        $workspace = Mockery::mock(Workspace::class);
        $workspace->shouldReceive('getAttribute')
            ->with(NotificationRecipients::ROUTES_KEY)
            ->andReturn($value);

        return $workspace;
    }

    public function test_reads_an_array_routing_map(): void
    {
        $routes = (new NotificationRecipients)->routes(
            $this->workspaceWithRoutes(['billing' => [1, 2], 'operations' => [3]]),
        );

        $this->assertSame(['billing' => [1, 2], 'operations' => [3]], $routes);
    }

    public function test_decodes_a_json_string_routing_map(): void
    {
        $routes = (new NotificationRecipients)->routes(
            $this->workspaceWithRoutes('{"billing":[1]}'),
        );

        $this->assertSame(['billing' => [1]], $routes);
    }

    public function test_returns_empty_map_when_unset(): void
    {
        $this->assertSame([], (new NotificationRecipients)->routes($this->workspaceWithRoutes(null)));
    }

    public function test_categories_cover_the_four_buckets(): void
    {
        $this->assertSame(
            ['review_growth', 'reputation', 'operations', 'billing'],
            NotificationCategory::all(),
        );
    }

    /** @return \Illuminate\Support\Collection<int, object> */
    private function members(): \Illuminate\Support\Collection
    {
        return collect([
            (object) ['id' => 1, 'pivot' => (object) ['role' => 'owner']],
            (object) ['id' => 2, 'pivot' => (object) ['role' => 'member']],
            (object) ['id' => 3, 'pivot' => (object) ['role' => 'member']],
            (object) ['id' => 4, 'pivot' => (object) ['role' => 'guest']],
        ]);
    }

    public function test_everyone_token_expands_to_all_members(): void
    {
        $ids = (new NotificationRecipients)->expandSelection(['everyone'], $this->members());

        $this->assertSame([1, 2, 3, 4], $ids);
    }

    public function test_role_token_expands_to_members_with_that_role(): void
    {
        $ids = (new NotificationRecipients)->expandSelection(['role:member'], $this->members());

        $this->assertSame([2, 3], $ids);
    }

    public function test_mixed_selection_dedupes_ids(): void
    {
        // The owner (id 1) is selected both individually and via the owners group.
        $ids = (new NotificationRecipients)->expandSelection(['role:owner', 1, 2], $this->members());

        $this->assertSame([1, 2], $ids);
    }
}
