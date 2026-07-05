<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules\Pages;

use App\Filament\App\Pages\Reports;
use App\Filament\App\Resources\ReportSchedules\ReportScheduleResource;
use App\Models\ReportSchedule;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListReportSchedules extends ListRecords
{
    protected static string $resource = ReportScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Schedules are created FROM the report builder ("Send on a
            // schedule") — it owns the full report configuration. Hidden while
            // empty so only the centered empty-state button shows.
            Action::make('new')
                ->label(__('resources/report_schedules.empty_cta'))
                ->icon(Heroicon::OutlinedPlus)
                ->url(Reports::getUrl())
                ->visible(fn (): bool => ReportSchedule::query()->exists()),
        ];
    }
}
