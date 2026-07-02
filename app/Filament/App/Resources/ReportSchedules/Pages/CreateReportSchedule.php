<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules\Pages;

use App\Filament\App\Resources\ReportSchedules\ReportScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReportSchedule extends CreateRecord
{
    protected static string $resource = ReportScheduleResource::class;
}
