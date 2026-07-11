<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReportSchedule;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Report schedules cover several locations via location_ids; the legacy
 * single location_id column mirrors a one-location selection so old readers
 * never see stale data after an edit.
 */
class ReportScheduleLocationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('report_schedules', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->string('frequency')->default('monthly');
            $table->integer('send_day')->default(1);
            $table->string('period')->default('last_month');
            $table->string('language')->default('en');
            $table->unsignedInteger('location_id')->nullable();
            $table->json('location_ids')->nullable();
            $table->boolean('compare')->default(true);
            $table->json('blocks')->nullable();
            $table->json('recipients')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });
    }

    private function makeSchedule(array $attributes = []): ReportSchedule
    {
        return ReportSchedule::create(array_merge([
            'name' => 'Monthly report',
            'frequency' => 'monthly',
            'send_day' => 1,
            'period' => 'last_month',
        ], $attributes));
    }

    public function test_single_selection_mirrors_into_location_id(): void
    {
        $schedule = $this->makeSchedule(['location_ids' => [7]]);

        $this->assertSame([7], $schedule->location_ids);
        $this->assertSame(7, (int) $schedule->location_id);
    }

    public function test_multi_selection_clears_the_single_column(): void
    {
        $schedule = $this->makeSchedule(['location_ids' => [3, 9]]);

        $this->assertSame([3, 9], $schedule->location_ids);
        $this->assertNull($schedule->location_id);
    }

    public function test_clearing_the_selection_clears_both_columns(): void
    {
        $schedule = $this->makeSchedule(['location_ids' => [3, 9]]);

        $schedule->update(['location_ids' => []]);
        $schedule->refresh();

        $this->assertNull($schedule->location_ids);
        $this->assertNull($schedule->location_id);
    }

    public function test_string_ids_from_the_form_are_normalized_to_integers(): void
    {
        $schedule = $this->makeSchedule(['location_ids' => ['4', '11']]);

        $this->assertSame([4, 11], $schedule->location_ids);
    }
}
