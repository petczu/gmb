<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules\Pages;

use App\Filament\App\Resources\ReportSchedules\ReportScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReportSchedule extends EditRecord
{
    protected static string $resource = ReportScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
