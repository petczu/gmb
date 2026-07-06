<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\AiBudgetAlertMail;
use App\Models\AiCreditLedger;
use App\Services\Ai\AiSpend;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * System-wide AI spend aggregates + the global budget alert. The ledger is
 * pinned to the central "mysql" connection, so that connection is redefined to
 * an in-memory sqlite for these tests.
 */
class AiBudgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('ai_credit_ledger', function ($table): void {
            $table->increments('id');
            $table->string('workspace_id');
            $table->integer('delta')->default(0);
            $table->integer('balance_after')->nullable();
            $table->string('reason');
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 12, 6)->nullable();
            $table->string('ref_type')->nullable();
            $table->string('ref_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-15 12:00:00'));
        config()->set('superadmin.emails', ['admin@example.test']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Schema::connection('mysql')->dropIfExists('ai_credit_ledger');
        parent::tearDown();
    }

    private function entry(float $cost, string $reason = 'auto_reply', ?string $createdAt = null, string $workspace = 'ws-1'): void
    {
        $row = new AiCreditLedger([
            'workspace_id' => $workspace,
            'delta' => 0,
            'reason' => $reason,
            'model' => 'claude-sonnet-4-6',
            'input_tokens' => 1000,
            'output_tokens' => 200,
            'cost_usd' => $cost,
        ]);
        $row->created_at = $createdAt ?? now();
        $row->save();
    }

    public function test_month_spend_only_counts_the_current_month(): void
    {
        $this->entry(2.50);
        $this->entry(1.25);
        $this->entry(99.0, createdAt: '2026-06-10 09:00:00'); // previous month

        $this->assertEqualsWithDelta(3.75, app(AiSpend::class)->monthSpendUsd(), 0.001);
    }

    public function test_stats_and_breakdowns(): void
    {
        $this->entry(1.0, 'auto_reply', workspace: 'ws-1');
        $this->entry(2.0, 'report', workspace: 'ws-2');
        $this->entry(4.0, createdAt: '2026-06-10 09:00:00'); // last month

        $spend = app(AiSpend::class);
        $stats = $spend->stats();

        $this->assertEqualsWithDelta(3.0, $stats['this_month'], 0.001);
        $this->assertEqualsWithDelta(4.0, $stats['last_month'], 0.001);
        $this->assertEqualsWithDelta(7.0, $stats['total'], 0.001);
        $this->assertSame(2, $stats['calls']);

        $this->assertSame('ws-2', $spend->byWorkspace()->first()->workspace_id);
        $this->assertSame('report', $spend->byReason()->first()->reason);
        $this->assertCount(1, $spend->byModel());
    }

    public function test_budget_check_alerts_once_at_eighty_percent(): void
    {
        Mail::fake();
        config()->set('services.ai.monthly_budget_usd', 10);

        $this->entry(8.5); // 85%

        $this->artisan('ai:budget-check')->assertSuccessful();
        $this->artisan('ai:budget-check')->assertSuccessful(); // deduped

        Mail::assertSent(AiBudgetAlertMail::class, 1);
        Mail::assertSent(AiBudgetAlertMail::class, function (AiBudgetAlertMail $mail): bool {
            return $mail->percent === 80 && $mail->hasTo('admin@example.test');
        });
    }

    public function test_budget_check_escalates_to_hundred_percent(): void
    {
        Mail::fake();
        config()->set('services.ai.monthly_budget_usd', 10);

        $this->entry(8.5);
        $this->artisan('ai:budget-check'); // 80% alert

        $this->entry(2.0); // now 105%
        $this->artisan('ai:budget-check');

        Mail::assertSent(AiBudgetAlertMail::class, 2);
        Mail::assertSent(AiBudgetAlertMail::class, fn (AiBudgetAlertMail $mail): bool => $mail->percent === 100);
    }

    public function test_budget_check_is_a_noop_without_a_budget(): void
    {
        Mail::fake();
        config()->set('services.ai.monthly_budget_usd', null);

        $this->entry(999.0);

        $this->artisan('ai:budget-check')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
