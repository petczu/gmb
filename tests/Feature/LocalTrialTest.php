<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use App\Services\Billing\SubscriptionGate;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Tests\TestCase;

/**
 * The card-less local trial: "Start trial" sets Cashier's generic trial
 * (workspace.trial_ends_at + trial_plan) with no Stripe objects. Plan gates,
 * the subscription gate and trial bookkeeping must all honour it. Workspace
 * persistence + Stripe subscription lookups are mocked (no central DB).
 */
class LocalTrialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Make billing "enabled" so the gate logic actually runs.
        config()->set('cashier.secret', 'sk_test_fake');
        config()->set('services.billing.prices.starter', 'price_starter');
        config()->set('services.billing.trial_days', 14);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function workspace(bool $hasStripeSubscriptions = false): Workspace
    {
        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('exists')->andReturn($hasStripeSubscriptions);

        $workspace = Mockery::mock(Workspace::class)->makePartial();
        $workspace->shouldReceive('save')->andReturnTrue();
        $workspace->shouldReceive('subscription')->andReturnNull();
        $workspace->shouldReceive('subscriptions')->andReturn($relation);

        return $workspace;
    }

    public function test_local_trial_starts_without_stripe(): void
    {
        $workspace = $this->workspace();
        $billing = app(LocationBilling::class);

        $this->assertFalse($billing->hasUsedTrial($workspace));

        $billing->startLocalTrial($workspace, 'growth');

        $this->assertTrue($workspace->onGenericTrial());
        $this->assertTrue($billing->onTrial($workspace));
        $this->assertSame('growth', $workspace->trial_plan);
        $this->assertSame('growth', $billing->plan($workspace)?->key);
        $this->assertTrue(now()->addDays(13)->lessThan($billing->trialEndsAt($workspace)));
        $this->assertSame('trial', app(SubscriptionGate::class)->state($workspace));
        $this->assertTrue($billing->hasUsedTrial($workspace));
    }

    public function test_expired_local_trial_blocks_and_cannot_restart(): void
    {
        $workspace = $this->workspace();
        $billing = app(LocationBilling::class);

        $billing->startLocalTrial($workspace, 'growth');
        $workspace->trial_ends_at = now()->subDay();

        $this->assertFalse($billing->onTrial($workspace));
        $this->assertNull($billing->plan($workspace));
        $this->assertSame('needs_plan', app(SubscriptionGate::class)->state($workspace));

        // A second local trial must be a no-op.
        $billing->startLocalTrial($workspace, 'pro');
        $this->assertTrue($workspace->trial_ends_at->isPast());
        $this->assertSame('growth', $workspace->trial_plan);
    }

    public function test_unknown_plan_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(LocationBilling::class)->startLocalTrial($this->workspace(), 'nonsense');
    }
}
