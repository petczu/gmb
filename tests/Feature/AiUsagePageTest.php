<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\AiUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class AiUsagePageTest extends TestCase
{
    public function test_ai_usage_page_renders_with_page_filters(): void
    {
        config()->set('database.connections.mysql', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge('mysql');
        Schema::connection('mysql')->create('ai_credit_ledger', function ($table): void {
            $table->increments('id');
            $table->string('workspace_id');
            $table->integer('delta')->default(0);
            $table->integer('balance_after')->default(0);
            $table->string('reason');
            $table->string('model')->nullable();
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->string('ref_type')->nullable();
            $table->string('ref_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tenants', function ($table): void {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();
        });
        DB::connection('mysql')->table('ai_credit_ledger')->insert([
            'workspace_id' => 'ws-1', 'reason' => 'post_image', 'model' => 'gpt-image-2',
            'input_tokens' => 10, 'output_tokens' => 20, 'cost_usd' => 0.05,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $component = Livewire::test(AiUsage::class);
        $component->assertOk();
        $component->assertSee('Workspace');

        // The page-level filters narrow everything, including the table.
        $component->set('fModel', 'gpt-image-2')->assertOk();
        $component->set('fModel', 'other-model')->assertOk();
        $component->set('fFrom', now()->subDay()->toDateString())->assertOk();
    }
}
