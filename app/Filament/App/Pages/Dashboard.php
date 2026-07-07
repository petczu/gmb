<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Location;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                        Select::make('period')
                            ->label(__('common.period'))
                            ->options(__('common.periods'))
                            ->default('last_30')
                            ->selectablePlaceholder(false)
                            ->live(),

                        Select::make('location_id')
                            ->label(__('common.location'))
                            ->placeholder(__('common.all_locations'))
                            ->options(fn (): array => tenancy()->initialized
                                ? Location::query()->orderBy('name')->pluck('name', 'id')->all()
                                : []),

                        DatePicker::make('startDate')
                            ->label(__('common.from'))
                            ->native(false)
                            ->maxDate(now())
                            ->prefixIcon('heroicon-o-calendar')
                            ->visible(fn (callable $get): bool => $get('period') === 'custom'),

                        DatePicker::make('endDate')
                            ->label(__('common.to'))
                            ->native(false)
                            ->maxDate(now())
                            ->prefixIcon('heroicon-o-calendar')
                            ->visible(fn (callable $get): bool => $get('period') === 'custom'),

                        Toggle::make('compare')
                            ->label(__('pages/dashboard.compare_to_previous'))
                            ->default(true)
                            ->inline(false),
                    ]),
                ]),
        ]);
    }
}
