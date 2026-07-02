<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReportSchedules;

use App\Filament\App\Resources\ReportSchedules\Pages\CreateReportSchedule;
use App\Filament\App\Resources\ReportSchedules\Pages\EditReportSchedule;
use App\Filament\App\Resources\ReportSchedules\Pages\ListReportSchedules;
use App\Filament\App\Resources\ReportSchedules\Schemas\ReportScheduleForm;
use App\Filament\App\Resources\ReportSchedules\Tables\ReportSchedulesTable;
use App\Models\ReportSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReportScheduleResource extends Resource
{
    protected static ?string $model = ReportSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'report-schedules';

    protected static ?string $recordTitleAttribute = 'name';

    // DB-level tenant isolation via stancl, not Filament native tenancy.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.scheduled_reports');
    }

    public static function form(Schema $schema): Schema
    {
        return ReportScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReportSchedulesTable::configure($table);
    }

    public static function canAccess(): bool
    {
        if (! (auth()->user()?->can('manage_reports') ?? false)) {
            return false;
        }

        $workspace = \App\Models\Workspace::find(session('current_workspace_id'));

        return $workspace !== null
            && app(\App\Services\Billing\LocationBilling::class)->allows($workspace, \App\Billing\Plans::SCHEDULED_REPORTS);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportSchedules::route('/'),
            'create' => CreateReportSchedule::route('/create'),
            'edit' => EditReportSchedule::route('/{record}/edit'),
        ];
    }
}
