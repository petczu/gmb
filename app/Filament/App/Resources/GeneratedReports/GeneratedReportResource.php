<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\GeneratedReports;

use App\Filament\App\Clusters\ReportsCluster;
use App\Filament\App\Resources\GeneratedReports\Pages\ListGeneratedReports;
use App\Filament\App\Resources\GeneratedReports\Tables\GeneratedReportsTable;
use App\Models\GeneratedReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GeneratedReportResource extends Resource
{
    protected static ?string $model = GeneratedReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = ReportsCluster::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'generated-reports';

    protected static ?string $recordTitleAttribute = 'title';

    // DB-level tenant isolation via stancl, not Filament native tenancy.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.generated_reports');
    }

    public static function table(Table $table): Table
    {
        return GeneratedReportsTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('view_reports') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeneratedReports::route('/'),
        ];
    }
}
