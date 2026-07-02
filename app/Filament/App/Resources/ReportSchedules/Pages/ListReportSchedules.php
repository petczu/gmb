<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules\Pages;

use App\Filament\App\Resources\ReportSchedules\ReportScheduleResource;
use App\Models\ReportSchedule;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReportSchedules extends ListRecords
{
    protected static string $resource = ReportScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Hidden while empty so only the centered empty-state button shows.
            CreateAction::make()->visible(fn (): bool => ReportSchedule::query()->exists()),
        ];
    }
}
