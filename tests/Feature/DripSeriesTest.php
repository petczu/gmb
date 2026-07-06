<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\Templates\EmailTemplateCatalog;
use App\Models\User;
use App\Services\Onboarding\DripSeries;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mockery;
use Tests\TestCase;

/**
 * Selection logic of the onboarding email series: track by ownership, day
 * windows, dedup, opt-out and guest exclusion. Membership is mocked so no
 * central DB is needed (same approach as WorkspaceSwitchTest).
 */
class DripSeriesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function user(string $role, string $membershipType, int $daysAgo, bool $optIn = true): User
    {
        $relation = Mockery::mock(BelongsToMany::class);
        $relation->shouldReceive('pluck')->with('workspace_user.role')->andReturn(collect([$role]));
        $relation->shouldReceive('pluck')->with('workspace_user.membership_type')->andReturn(collect([$membershipType]));

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('workspaces')->andReturn($relation);
        $user->shouldReceive('getAttribute')->with('product_emails')->andReturn($optIn);
        $user->shouldReceive('getAttribute')->with('created_at')->andReturn(CarbonImmutable::now()->subDays($daysAgo));

        return $user;
    }

    public function test_owner_gets_first_step_on_day_one(): void
    {
        $step = (new DripSeries)->dueStep($this->user('owner', 'internal', 1), []);

        $this->assertSame('drip_inbox', $step);
    }

    public function test_steps_progress_and_dedup(): void
    {
        $series = new DripSeries;
        $user = $this->user('owner', 'internal', 3);

        // Day 3: inbox not yet sent → it comes first (still inside its window).
        $this->assertSame('drip_inbox', $series->dueStep($user, []));
        // Inbox already sent → automation is due.
        $this->assertSame('drip_automation', $series->dueStep($user, ['drip_inbox']));
    }

    public function test_old_users_get_no_backlog(): void
    {
        // Signed up long before the feature: every window has passed.
        $this->assertNull((new DripSeries)->dueStep($this->user('owner', 'internal', 60), []));
    }

    public function test_member_track_and_guest_exclusion(): void
    {
        $series = new DripSeries;

        $this->assertSame('drip_member', $series->dueStep($this->user('member', 'internal', 1), []));
        $this->assertNull($series->dueStep($this->user('guest', 'guest', 1), []));
    }

    public function test_opt_out_stops_everything(): void
    {
        $this->assertNull((new DripSeries)->dueStep($this->user('owner', 'internal', 1, optIn: false), []));
    }

    public function test_track_keys_have_templates_in_catalog(): void
    {
        foreach (DripSeries::keys() as $key) {
            $this->assertTrue(EmailTemplateCatalog::has($key), "missing catalog template: {$key}");
            $this->assertNotSame('', EmailTemplateCatalog::defaultBody($key, 'en'));
            $this->assertNotSame('', EmailTemplateCatalog::defaultBody($key, 'de'));
        }
    }
}
