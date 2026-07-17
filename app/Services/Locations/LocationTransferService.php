<?php

declare(strict_types=1);

namespace App\Services\Locations;

use App\Models\LocationGroup;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Move a single location, with everything tied EXCLUSIVELY to it, from one
 * workspace's tenant database into another's. Shared objects (posts,
 * automations and report schedules that also target other locations, or
 * all_locations automations) stay behind untouched.
 *
 * Rows are copied with the raw query builder (not Eloquent) so no model events
 * fire during the move — e.g. the reply.published webhook must not re-fire while
 * copying already-answered reviews. Foreign keys are remapped as new rows are
 * inserted. The Google/Zernio connection is per-workspace, so the moved location
 * lands disconnected (zernio_account_id nulled) and must be reconnected in the
 * target workspace afterwards.
 */
class LocationTransferService
{
    public function __construct(private LocationBilling $billing) {}

    /**
     * Count what a move would carry across, without changing anything.
     *
     * @return array<string, int|string>
     */
    public function preview(int $locationId, Workspace $source): array
    {
        return $this->within($source, function () use ($locationId): array {
            $location = DB::table('locations')->where('id', $locationId)->first();
            if ($location === null) {
                throw new RuntimeException('Location not found in the source workspace.');
            }

            $reviewIds = DB::table('reviews')->where('location_id', $locationId)->pluck('id');

            return [
                'location' => (string) $location->name,
                'reviews' => $reviewIds->count(),
                'queue_items' => $reviewIds->isEmpty() ? 0 : $this->safeCount(fn () => DB::table('auto_reply_queue_items')->whereIn('review_id', $reviewIds)->count()),
                'posts' => $this->exclusivePosts($locationId)->count(),
                'auto_reply_rules' => $this->safeCount(fn () => DB::table('auto_reply_rules')->where('location_id', $locationId)->count()),
                'scheduled_updates' => $this->safeCount(fn () => DB::table('scheduled_listing_updates')->where('location_id', $locationId)->count()),
                'automations' => $this->exclusiveAutomations($locationId)->count(),
                'report_schedules' => $this->exclusiveReports($locationId)->count(),
                'agents' => count($this->referencedAgentIds($locationId, $reviewIds)),
            ];
        });
    }

    /**
     * Perform the move. Copies to the target first, then deletes from the
     * source, then re-syncs the billed location count on both workspaces.
     */
    public function transfer(int $locationId, Workspace $source, Workspace $target): void
    {
        if ($source->id === $target->id) {
            throw new RuntimeException('Source and target workspaces are the same.');
        }

        // 1. Read everything out of the source into memory.
        $data = $this->within($source, function () use ($locationId): array {
            $location = DB::table('locations')->where('id', $locationId)->first();
            if ($location === null) {
                throw new RuntimeException('Location not found in the source workspace.');
            }

            $reviews = DB::table('reviews')->where('location_id', $locationId)->get();
            $reviewIds = $reviews->pluck('id');

            return [
                'location' => (array) $location,
                'reviews' => $reviews->map(fn ($r): array => (array) $r)->all(),
                'queue' => $reviewIds->isEmpty() ? [] : $this->safeRows(fn () => DB::table('auto_reply_queue_items')->whereIn('review_id', $reviewIds)->get()),
                'posts' => $this->exclusivePosts($locationId)->map(fn ($r): array => (array) $r)->all(),
                'rules' => $this->safeRows(fn () => DB::table('auto_reply_rules')->where('location_id', $locationId)->get()),
                'scheduled' => $this->safeRows(fn () => DB::table('scheduled_listing_updates')->where('location_id', $locationId)->get()),
                'automations' => $this->exclusiveAutomations($locationId)->map(fn ($r): array => (array) $r)->all(),
                'reports' => $this->exclusiveReports($locationId)->map(fn ($r): array => (array) $r)->all(),
                'agents' => $this->safeRows(fn () => DB::table('ai_agents')->whereIn('id', $this->referencedAgentIds($locationId, $reviewIds))->get()),
            ];
        });

        // 2. Write into the target, remapping ids, inside a transaction.
        $this->within($target, function () use ($data): void {
            DB::transaction(function () use ($data): void {
                // Agents first: build old id => new id.
                $agentMap = [];
                foreach ($data['agents'] as $agent) {
                    $agentMap[(int) $agent['id']] = $this->copy('ai_agents', $agent);
                }

                // The location itself, disconnected from the source's Zernio account.
                $newLocationId = $this->copy('locations', $data['location'], [
                    'zernio_account_id' => null,
                ]);

                // Reviews (+ their queue items), remapping the review id.
                $reviewMap = [];
                foreach ($data['reviews'] as $review) {
                    $reviewMap[(int) $review['id']] = $this->copy('reviews', $review, [
                        'location_id' => $newLocationId,
                        'ai_agent_id' => $this->mapAgent($review['ai_agent_id'] ?? null, $agentMap),
                    ]);
                }
                foreach ($data['queue'] as $item) {
                    $this->copy('auto_reply_queue_items', $item, [
                        'review_id' => $reviewMap[(int) $item['review_id']] ?? null,
                        'ai_agent_id' => $this->mapAgent($item['ai_agent_id'] ?? null, $agentMap),
                    ]);
                }

                $ids = json_encode([$newLocationId]);
                foreach ($data['posts'] as $post) {
                    $this->copy('posts', $post, ['location_ids' => $ids]);
                }
                foreach ($data['rules'] as $rule) {
                    $this->copy('auto_reply_rules', $rule, ['location_id' => $newLocationId]);
                }
                foreach ($data['scheduled'] as $update) {
                    $this->copy('scheduled_listing_updates', $update, ['location_id' => $newLocationId]);
                }
                foreach ($data['automations'] as $automation) {
                    $this->copy('automations', $automation, [
                        'location_ids' => $ids,
                        'ai_agent_id' => $this->mapAgent($automation['ai_agent_id'] ?? null, $agentMap),
                    ]);
                }
                foreach ($data['reports'] as $report) {
                    $this->copy('report_schedules', $report, [
                        'location_id' => $newLocationId,
                        'location_ids' => $ids,
                    ]);
                }
            });
        });

        // 3. Delete the moved rows from the source, inside a transaction.
        $this->within($source, function () use ($data, $locationId): void {
            DB::transaction(function () use ($data, $locationId): void {
                $reviewIds = array_map(fn (array $r): int => (int) $r['id'], $data['reviews']);
                if ($reviewIds !== []) {
                    if (Schema::hasTable('auto_reply_queue_items')) {
                        DB::table('auto_reply_queue_items')->whereIn('review_id', $reviewIds)->delete();
                    }
                    DB::table('reviews')->whereIn('id', $reviewIds)->delete();
                }
                $this->deleteRows('posts', $data['posts']);
                $this->deleteRows('auto_reply_rules', $data['rules']);
                $this->deleteRows('scheduled_listing_updates', $data['scheduled']);
                $this->deleteRows('automations', $data['automations']);
                $this->deleteRows('report_schedules', $data['reports']);

                // Drop the location from any group it was in (delete emptied groups).
                LocationGroup::detachLocation($locationId);

                DB::table('locations')->where('id', $locationId)->delete();
            });
        });

        // 4. Re-bill both workspaces for their new location counts.
        $this->billing->syncQuantity($source);
        $this->billing->syncQuantity($target);
    }

    /** Posts whose ONLY target is this location. */
    private function exclusivePosts(int $locationId): Collection
    {
        return collect($this->safeRows(fn () => DB::table('posts')->whereJsonLength('location_ids', 1)->whereJsonContains('location_ids', $locationId)->get()));
    }

    /** Automations bound to this location alone (not all_locations, single id). */
    private function exclusiveAutomations(int $locationId): Collection
    {
        return collect($this->safeRows(fn () => DB::table('automations')->where('all_locations', false)->whereJsonLength('location_ids', 1)->whereJsonContains('location_ids', $locationId)->get()));
    }

    /** Report schedules bound to this location alone. */
    private function exclusiveReports(int $locationId): Collection
    {
        return collect($this->safeRows(fn () => DB::table('report_schedules')->whereJsonLength('location_ids', 1)->whereJsonContains('location_ids', $locationId)->get()));
    }

    /**
     * AI agent ids referenced by anything that will move: the location's review
     * replies, their queue items, and the exclusive automations.
     *
     * @param  Collection<int, int>  $reviewIds
     * @return list<int>
     */
    private function referencedAgentIds(int $locationId, Collection $reviewIds): array
    {
        if (! Schema::hasTable('ai_agents')) {
            return [];
        }

        $fromReviews = collect($this->safeRows(fn () => DB::table('reviews')->where('location_id', $locationId)->whereNotNull('ai_agent_id')->get(['ai_agent_id'])))->pluck('ai_agent_id');
        $fromQueue = $reviewIds->isEmpty() ? collect() : collect($this->safeRows(fn () => DB::table('auto_reply_queue_items')->whereIn('review_id', $reviewIds)->whereNotNull('ai_agent_id')->get(['ai_agent_id'])))->pluck('ai_agent_id');
        $fromAutomations = $this->exclusiveAutomations($locationId)->pluck('ai_agent_id')->filter();

        return $fromReviews->merge($fromQueue)->merge($fromAutomations)
            ->filter()->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }

    /**
     * Run a read query, returning its rows as arrays — or an empty list if the
     * table or a column is missing (a tenant behind on migrations must not crash
     * the whole move; that data simply doesn't exist there yet).
     *
     * @param  Closure(): Collection  $query
     * @return list<array<string, mixed>>
     */
    private function safeRows(Closure $query): array
    {
        try {
            return $query()->map(fn ($r): array => (array) $r)->all();
        } catch (QueryException) {
            return [];
        }
    }

    /**
     * Run a count query, returning 0 if the table or a column is missing.
     *
     * @param  Closure(): int  $query
     */
    private function safeCount(Closure $query): int
    {
        try {
            return $query();
        } catch (QueryException) {
            return 0;
        }
    }

    /**
     * Insert a copied row (raw values from the source) into the current tenant,
     * dropping its old id and applying column overrides. Returns the new id.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $overrides
     */
    private function copy(string $table, array $row, array $overrides = []): int
    {
        unset($row['id']);

        return (int) DB::table($table)->insertGetId(array_merge($row, $overrides));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function deleteRows(string $table, array $rows): void
    {
        $ids = array_map(fn (array $r): int => (int) $r['id'], $rows);
        if ($ids !== []) {
            DB::table($table)->whereIn('id', $ids)->delete();
        }
    }

    /**
     * @param  array<int, int>  $agentMap
     */
    private function mapAgent(mixed $oldId, array $agentMap): ?int
    {
        return $oldId === null ? null : ($agentMap[(int) $oldId] ?? null);
    }

    /**
     * Run a callback with tenancy initialized for the given workspace, restoring
     * whatever tenant (if any) was active before.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function within(Workspace $workspace, Closure $callback): mixed
    {
        $previous = tenant();
        $this->activateTenant($workspace);

        try {
            return $callback();
        } finally {
            $this->restoreTenant($previous);
        }
    }

    /** Overridable for tests (which run on a single connection). */
    protected function activateTenant(Workspace $workspace): void
    {
        tenancy()->initialize($workspace);
    }

    protected function restoreTenant(?Workspace $previous): void
    {
        if ($previous !== null) {
            tenancy()->initialize($previous);
        } else {
            tenancy()->end();
        }
    }
}
