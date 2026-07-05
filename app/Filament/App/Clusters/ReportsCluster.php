<?php

declare(strict_types=1);

namespace App\Filament\App\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * Groups the three report surfaces (builder, schedules, history) behind ONE
 * navigation item; inside, Filament renders them as sub-navigation.
 */
class ReportsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    // Ungrouped on purpose: the cluster itself IS the "Reports" pillar — a
    // group heading above a single same-named item read as a duplicate.
    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'reports';

    public static function getNavigationLabel(): string
    {
        return __('nav.group_reports');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('nav.group_reports');
    }
}
