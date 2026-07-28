<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SyncLocationReviewsJob;
use App\Models\Location;
use App\Models\Workspace;
use App\Services\Reviews\ReviewProvider;
use App\Services\Reviews\ReviewProviderFactory;
use App\Services\Reviews\ReviewSync;
use Carbon\CarbonInterface;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Stancl\Tenancy\Tenancy;
use Tests\TestCase;

/**
 * The queued per-location sync must fetch incrementally (reviews updated since
 * the last successful sync, with an overlap cushion) while the first sync and
 * the manual "sync now" path stay full refreshes. Tables are built on the test
 * connection directly; tenancy is not bootstrapped (syncLocation is called with
 * tenancyManaged: false, and the job's failed() hook gets a mocked Tenancy).
 */
class ReviewSyncIncrementalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('external_id')->nullable();
            $table->string('zernio_account_id')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->float('rating')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function ($table): void {
            $table->increments('id');
            $table->unsignedInteger('location_id');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('tenants');

        parent::tearDown();
    }

    /**
     * @return object{provider: ReviewProvider, sync: ReviewSync}
     */
    private function syncWithCapturingProvider(): object
    {
        $provider = new class implements ReviewProvider
        {
            /** @var array<int, ?CarbonInterface> */
            public array $sinceCalls = [];

            public function listLocations(string $accountId): array
            {
                return [];
            }

            public function listReviews(string $accountId, ?string $locationExternalId = null, ?CarbonInterface $since = null): array
            {
                $this->sinceCalls[] = $since;

                return [];
            }

            public function reply(string $accountId, string $reviewExternalId, string $comment, ?string $locationExternalId = null): void {}

            public function deleteReply(string $accountId, string $reviewExternalId, ?string $locationExternalId = null): void {}
        };

        $factory = new class($provider) extends ReviewProviderFactory
        {
            public function __construct(private readonly ReviewProvider $provider) {}

            public function make(): ReviewProvider
            {
                return $this->provider;
            }
        };

        return (object) ['provider' => $provider, 'sync' => new ReviewSync($factory)];
    }

    private function location(?string $lastSyncedAt): Location
    {
        $location = new Location;
        $location->forceFill([
            'name' => 'Acme Downtown',
            'external_id' => 'loc-1',
            'zernio_account_id' => 'acc-1',
            'last_synced_at' => $lastSyncedAt,
        ])->save();

        return $location->refresh();
    }

    public function test_synced_location_fetches_incrementally_with_one_day_overlap(): void
    {
        $stub = $this->syncWithCapturingProvider();
        $location = $this->location('2026-07-27 10:00:00');

        $stub->sync->syncLocation(new Workspace, $location, tenancyManaged: false);

        $this->assertCount(1, $stub->provider->sinceCalls);
        $this->assertSame('2026-07-26 10:00:00', $stub->provider->sinceCalls[0]?->format('Y-m-d H:i:s'));
        $this->assertNull($location->refresh()->last_sync_error);
    }

    public function test_first_sync_fetches_the_full_history(): void
    {
        $stub = $this->syncWithCapturingProvider();
        $location = $this->location(null);

        $result = $stub->sync->syncLocation(new Workspace, $location, tenancyManaged: false);

        $this->assertSame([null], $stub->provider->sinceCalls);
        $this->assertNotNull($result['first_synced']);
        $this->assertNotNull($location->refresh()->last_synced_at);
    }

    public function test_full_refresh_fetches_the_full_history_even_when_synced_before(): void
    {
        $stub = $this->syncWithCapturingProvider();
        $location = $this->location('2026-07-27 10:00:00');

        $stub->sync->syncLocation(new Workspace, $location, tenancyManaged: false, fullRefresh: true);

        $this->assertSame([null], $stub->provider->sinceCalls);
    }

    public function test_job_failure_records_a_readable_sync_error_on_the_location(): void
    {
        Schema::create('tenants', function ($table): void {
            $table->string('id')->primary();
            $table->json('data')->nullable();
            $table->timestamps();
        });
        DB::table('tenants')->insert(['id' => 'ws-1', 'data' => '{}']);

        $location = $this->location('2026-07-27 10:00:00');

        $tenancy = Mockery::mock(Tenancy::class);
        $tenancy->shouldReceive('initialize')->once();
        $tenancy->shouldReceive('end')->once();
        $this->app->instance(Tenancy::class, $tenancy);

        $job = new SyncLocationReviewsJob('ws-1', (int) $location->id);
        $job->failed(new MaxAttemptsExceededException('App\Jobs\SyncLocationReviewsJob has been attempted too many times.'));

        $this->assertSame(
            'Review sync timed out repeatedly; it will retry on the next scheduled sync.',
            $location->refresh()->last_sync_error,
        );
    }
}
