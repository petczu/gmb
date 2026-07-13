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
        // No state supplied → conditional nudges stay out of the way.
        $step = (new DripSeries)->dueStep($this->user('owner', 'internal', 1), []);

        $this->assertSame('drip_inbox', $step);
    }

    public function test_connect_nudge_comes_first_while_no_location(): void
    {
        $series = new DripSeries;
        $user = $this->user('owner', 'internal', 1);

        // No location yet → the activation nudge wins over the inbox step.
        $this->assertSame('drip_connect', $series->dueStep($user, [], state: ['has_locations' => false]));
        // Location connected → the nudge is silently skipped.
        $this->assertSame('drip_inbox', $series->dueStep($user, [], state: ['has_locations' => true]));
        // Nudge already sent, still no location → continue the normal series.
        $this->assertSame('drip_inbox', $series->dueStep($user, ['drip_connect'], state: ['has_locations' => false]));
    }

    public function test_steps_progress_and_dedup(): void
    {
        $series = new DripSeries;
        $user = $this->user('owner', 'internal', 3);
        $state = ['has_locations' => true, 'has_automations' => false];

        // Day 3: inbox not yet sent → it comes first (still inside its window).
        $this->assertSame('drip_inbox', $series->dueStep($user, [], state: $state));
        // Inbox already sent, no automations configured → the automation nudge.
        $this->assertSame('drip_automation', $series->dueStep($user, ['drip_inbox'], state: $state));
        // Automations already set up → the nudge is skipped entirely.
        $this->assertNull($series->dueStep($user, ['drip_inbox'], state: ['has_locations' => true, 'has_automations' => true]));
    }

    public function test_feature_nudges_fire_only_while_unused(): void
    {
        $series = new DripSeries;
        $user = $this->user('owner', 'internal', 5);
        $sent = ['drip_connect', 'drip_inbox', 'drip_automation'];

        // Day 5, no review page yet → the collection-page step.
        $this->assertSame('drip_growth', $series->dueStep($user, $sent, state: ['has_active_review_page' => false]));
        // Page live but no competitors → the competitors nudge.
        $this->assertSame('drip_competitors', $series->dueStep($user, $sent, state: [
            'has_active_review_page' => true, 'has_competitors' => false,
        ]));
        // Everything set up → nothing due on day 5.
        $this->assertNull($series->dueStep($user, $sent, state: [
            'has_active_review_page' => true, 'has_competitors' => true,
        ]));
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

    public function test_user_without_any_membership_gets_nothing(): void
    {
        // A removed guest / not-yet-accepted invitee: nothing to onboard into.
        $relation = Mockery::mock(BelongsToMany::class);
        $relation->shouldReceive('pluck')->with('workspace_user.role')->andReturn(collect());
        $relation->shouldReceive('pluck')->with('workspace_user.membership_type')->andReturn(collect());

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('workspaces')->andReturn($relation);
        $user->shouldReceive('getAttribute')->with('product_emails')->andReturn(true);
        $user->shouldReceive('getAttribute')->with('created_at')->andReturn(CarbonImmutable::now()->subDays(1));

        $this->assertNull((new DripSeries)->dueStep($user, []));
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
