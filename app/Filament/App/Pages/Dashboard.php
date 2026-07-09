<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Location;
use App\Support\DashboardWidgets;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    /** Show/hide dashboard widgets, saved per user (null = all visible). */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('customize')
                ->label(__('pages/dashboard.customize'))
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->color('gray')
                ->modalHeading(__('pages/dashboard.customize_heading'))
                ->modalDescription(__('pages/dashboard.customize_desc'))
                ->schema([
                    CheckboxList::make('widgets')
                        ->hiddenLabel()
                        ->options(DashboardWidgets::labels())
                        ->default(DashboardWidgets::enabled())
                        ->bulkToggleable(),
                ])
                ->action(function (array $data): void {
                    $selected = array_values(array_intersect(DashboardWidgets::KEYS, (array) ($data['widgets'] ?? [])));

                    // Full selection is stored as null so widgets added later
                    // stay visible by default.
                    auth()->user()->forceFill([
                        'dashboard_widgets' => count($selected) === count(DashboardWidgets::KEYS) ? null : $selected,
                    ])->save();

                    Notification::make()->title(__('pages/dashboard.customize_saved'))->success()->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()
                ->columnSpanFull()
                // No filters while there's nothing to filter — the dashboard
                // shows the connect-first empty state instead.
                ->visible(fn (): bool => tenancy()->initialized && Location::query()->exists())
                ->schema([
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])->schema([
                        Select::make('period')
                            ->label(__('common.period'))
                            ->options(__('common.periods'))
                            ->default('last_7')
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

                    Actions::make([
                        Action::make('resetFilters')
                            ->label(__('pages/dashboard.reset_filters'))
                            ->icon(Heroicon::OutlinedArrowUturnLeft)
                            ->link()
                            ->color('gray')
                            ->action(function (Dashboard $livewire): void {
                                $livewire->filters = self::defaultFilters();
                                $livewire->getFiltersForm()->fill($livewire->filters);
                            }),
                    ]),
                ]),
        ]);
    }

    /** The filter form's initial state (also what Reset restores). */
    public static function defaultFilters(): array
    {
        return [
            'period' => 'last_7',
            'location_id' => null,
            'startDate' => null,
            'endDate' => null,
            'compare' => true,
        ];
    }
}
