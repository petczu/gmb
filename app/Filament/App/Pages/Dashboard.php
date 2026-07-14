<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Location;
use App\Support\DashboardWidgets;
use Filament\Actions\Action;
use Filament\Facades\Filament;
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
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    /**
     * Arrange mode: Customize toggles the per-widget controls (drag grip,
     * width toggle, hide) on the grid; while it is on, "Add widget" restores
     * anything hidden. The state lives on the page and is mirrored to the JS
     * via a browser event.
     */
    public bool $arranging = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addWidget')
                ->label(__('pages/dashboard.add_widget'))
                ->icon(Heroicon::OutlinedPlus)
                ->visible(fn (): bool => $this->arranging && $this->hiddenWidgetOptions() !== [])
                ->modalHeading(__('pages/dashboard.add_widget_heading'))
                ->modalDescription(__('pages/dashboard.add_widget_desc'))
                ->schema([
                    CheckboxList::make('widgets')
                        ->hiddenLabel()
                        ->options(fn (): array => $this->hiddenWidgetOptions()),
                ])
                ->action(function (array $data): void {
                    $restored = array_values(array_intersect(DashboardWidgets::KEYS, (array) ($data['widgets'] ?? [])));
                    if ($restored === []) {
                        return;
                    }

                    $enabled = array_values(array_unique([...DashboardWidgets::enabled(), ...$restored]));

                    // Full selection is stored as null so widgets added later
                    // stay visible by default.
                    auth()->user()->forceFill([
                        'dashboard_widgets' => count($enabled) === count(DashboardWidgets::KEYS) ? null : $enabled,
                    ])->save();

                    Notification::make()->title(__('pages/dashboard.widgets_restored'))->success()->send();
                }),

            Action::make('resetLayout')
                ->label(__('pages/dashboard.reset_layout'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->visible(fn (): bool => $this->arranging)
                ->requiresConfirmation()
                ->modalDescription(__('pages/dashboard.reset_layout_desc'))
                ->action(function (): void {
                    auth()->user()?->forceFill([
                        'dashboard_widgets' => null,
                        'dashboard_widget_order' => null,
                        'dashboard_widget_spans' => null,
                    ])->save();

                    Notification::make()->title(__('pages/dashboard.reset_layout_done'))->success()->send();

                    // Widgets render server-side; a reload applies the defaults.
                    $this->js('window.location.reload()');
                }),

            Action::make('customize')
                ->label(fn (): string => $this->arranging ? __('pages/dashboard.customize_done') : __('pages/dashboard.customize'))
                ->icon(fn (): Heroicon => $this->arranging ? Heroicon::OutlinedCheck : Heroicon::OutlinedAdjustmentsHorizontal)
                ->color(fn (): string => $this->arranging ? 'primary' : 'gray')
                ->action(function (): void {
                    $this->arranging = ! $this->arranging;
                    $this->dispatch('wgt-arranging', state: $this->arranging);
                }),
        ];
    }

    /** Widgets currently hidden by the user, key => label (for "Add widget"). */
    protected function hiddenWidgetOptions(): array
    {
        $hidden = array_values(array_diff(DashboardWidgets::KEYS, DashboardWidgets::enabled()));

        return array_intersect_key(DashboardWidgets::labels(), array_flip($hidden));
    }

    /** Widgets in the user's saved drag-and-drop order (default $sort otherwise). */
    public function getWidgets(): array
    {
        return collect(parent::getWidgets())
            ->sortBy(fn ($widget): int => DashboardWidgets::position(
                $widget instanceof WidgetConfiguration ? $widget->widget : (string) $widget,
            ))
            ->values()
            ->all();
    }

    /**
     * Persist a drag-and-drop reorder (called from the grid's JS). The payload
     * carries only the VISIBLE widgets' order keys; hidden ones keep their
     * previous relative positions at the end.
     *
     * @param  array<int, string>  $keys
     */
    public function reorderWidgets(array $keys): void
    {
        $known = array_keys(DashboardWidgets::classes());
        $keys = array_values(array_intersect(array_map('strval', $keys), $known));

        if ($keys === []) {
            return;
        }

        $rest = array_values(array_diff(DashboardWidgets::order(), $keys));

        auth()->user()->forceFill([
            'dashboard_widget_order' => [...$keys, ...$rest],
        ])->save();
    }

    /**
     * Hide one widget from the grid (the trash icon next to the drag grip).
     * Restoring happens through the Customize modal, which lists everything.
     */
    public function hideWidget(string $key): void
    {
        if (! in_array($key, DashboardWidgets::KEYS, true)) {
            return;
        }

        $enabled = array_values(array_diff(DashboardWidgets::enabled(), [$key]));

        auth()->user()->forceFill(['dashboard_widgets' => $enabled])->save();

        Notification::make()
            ->title(__('pages/dashboard.widget_hidden'))
            ->body(__('pages/dashboard.widget_hidden_body'))
            ->success()
            ->send();
    }

    /**
     * Flip one widget between full and half width (the arrows icon next to
     * the drag grip). Half-width widgets share a row, so left/right dragging
     * becomes meaningful.
     */
    public function toggleWidgetSpan(string $key): void
    {
        $classes = DashboardWidgets::classes();
        $class = $classes[$key] ?? null;

        if ($class === null) {
            return;
        }

        // Effective current width (user override, else the widget's default).
        $current = (new $class)->getColumnSpan();

        $spans = auth()->user()->dashboard_widget_spans ?? [];
        $spans[$key] = $current === 'full' ? 1 : 'full';

        auth()->user()->forceFill(['dashboard_widget_spans' => $spans])->save();
    }

    /**
     * Visible widgets' order keys, matching the grid's DOM order — feeds the
     * drag-and-drop JS (rendered via a PAGE_END render hook, hence static).
     *
     * @return list<string>
     */
    public static function visibleWidgetOrderKeys(): array
    {
        $classes = array_flip(DashboardWidgets::classes());

        return collect(Filament::getWidgets())
            ->map(fn ($widget): string => $widget instanceof WidgetConfiguration ? $widget->widget : (string) $widget)
            ->filter(fn (string $class): bool => isset($classes[$class]) && $class::canView())
            ->sortBy(fn (string $class): int => DashboardWidgets::position($class))
            ->map(fn (string $class): string => $classes[$class])
            ->values()
            ->all();
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
                            ->multiple()
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
            'location_id' => [],
            'startDate' => null,
            'endDate' => null,
            'compare' => true,
        ];
    }
}
