<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Competitor;
use App\Models\Location;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use App\Support\DashboardPeriod;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Competitor benchmark: track nearby businesses via the Google Places API and
 * compare their rating/review count against your own locations. Daily snapshots
 * (competitors:refresh) feed the period trends.
 */
class Competitors extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'competitors';

    protected string $view = 'filament.app.pages.competitors';

    public static function getNavigationLabel(): string
    {
        return __('pages/competitors.nav');
    }

    public function getTitle(): string
    {
        return __('pages/competitors.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('view_competitors') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_competitors') ?? false;
    }

    public function isConfigured(): bool
    {
        return app(PlacesClient::class)->configured();
    }

    /** Trend period preset (same options as the dashboard/report filters). */
    public string $trendPeriod = 'last_30';

    public ?string $trendFrom = null;

    public ?string $trendTo = null;

    /** @var array<int, array{reviews_delta: ?int, rating_delta: ?float, spark: list<int>}> */
    private array $trendCache = [];

    /** @var array<int, int> own new reviews per location for the window */
    private array $ownCache = [];

    protected function trendWindow(): DashboardPeriod
    {
        return DashboardPeriod::fromFilters([
            'period' => $this->trendPeriod,
            'startDate' => $this->trendFrom,
            'endDate' => $this->trendTo,
        ]);
    }

    protected function trendStart(): CarbonImmutable
    {
        return $this->trendWindow()->start;
    }

    protected function trendEnd(): CarbonImmutable
    {
        return $this->trendWindow()->end;
    }

    /**
     * @return array{reviews_delta: ?int, rating_delta: ?float, spark: list<int>}
     */
    protected function trendFor(Competitor $record): array
    {
        return $this->trendCache[$record->id]
            ??= app(CompetitorTrends::class)->summary($record, $this->trendStart(), $this->trendEnd());
    }

    protected function ownNewReviews(Competitor $record): int
    {
        return $this->ownCache[$record->location_id]
            ??= app(CompetitorTrends::class)->ownNewReviews((int) $record->location_id, $this->trendStart(), $this->trendEnd());
    }

    /** Tiny inline sparkline of the review-count snapshots in the window. */
    protected function sparkSvg(array $values): ?HtmlString
    {
        if (count($values) < 2) {
            return null;
        }

        $min = min($values);
        $max = max($values);
        $range = max(1, $max - $min);
        $step = 96 / (count($values) - 1);

        $points = [];
        foreach ($values as $i => $value) {
            $x = round($i * $step, 1);
            $y = round(22 - (($value - $min) / $range) * 18, 1); // 2..22 padding
            $points[] = $x.','.$y;
        }

        return new HtmlString(
            '<svg width="100" height="24" viewBox="0 0 100 24" fill="none" xmlns="http://www.w3.org/2000/svg">'
            .'<polyline points="'.implode(' ', $points).'" stroke="#2d19ec" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>'
            .'</svg>'
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Competitor::query()->with('location'))
            ->defaultSort('rating', 'desc')
            ->emptyStateHeading(__('pages/competitors.empty'))
            ->emptyStateDescription(__('pages/competitors.empty_desc'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('pages/competitors.col_name'))
                    ->weight('medium')
                    ->description(fn (Competitor $record): ?string => $record->address),

                TextColumn::make('rating')
                    ->label(__('pages/competitors.col_rating'))
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? number_format((float) $state, 1).' ★' : '—')
                    ->sortable(),

                TextColumn::make('reviews_count')
                    ->label(__('pages/competitors.col_reviews'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('vs')
                    ->label(__('pages/competitors.col_vs'))
                    ->badge()
                    ->state(fn (Competitor $record): string => $this->comparison($record))
                    ->color(fn (Competitor $record): string => $this->comparisonColor($record)),

                TextColumn::make('reviews_trend')
                    ->label(__('pages/competitors.col_new_reviews'))
                    ->state(function (Competitor $record): string {
                        $delta = $this->trendFor($record)['reviews_delta'];

                        // No history yet → ONE quiet hint, no noisy dashes.
                        return $delta === null
                            ? __('pages/competitors.collecting')
                            : ($delta > 0 ? '+'.$delta : (string) $delta);
                    })
                    ->description(function (Competitor $record): ?string {
                        $trend = $this->trendFor($record);

                        if ($trend['reviews_delta'] === null) {
                            return null;
                        }

                        $parts = [__('pages/competitors.you_delta', ['delta' => '+'.$this->ownNewReviews($record)])];

                        if ($trend['prev_reviews_delta'] !== null) {
                            $parts[] = __('pages/competitors.prev_delta', [
                                'delta' => ($trend['prev_reviews_delta'] > 0 ? '+' : '').$trend['prev_reviews_delta'],
                            ]);
                        }

                        return implode(' · ', $parts);
                    })
                    ->color(function (Competitor $record): string {
                        $delta = $this->trendFor($record)['reviews_delta'];

                        if ($delta === null) {
                            return 'gray';
                        }

                        // Green when YOU are growing at least as fast.
                        return $this->ownNewReviews($record) >= $delta ? 'success' : 'danger';
                    })
                    ->tooltip(__('pages/competitors.trend_hint')),

                TextColumn::make('rating_trend')
                    ->label(__('pages/competitors.col_rating_trend'))
                    ->state(function (Competitor $record): ?string {
                        $delta = $this->trendFor($record)['rating_delta'];

                        return match (true) {
                            $delta === null => null, // placeholder dash
                            abs($delta) < 0.005 => __('pages/competitors.no_change'),
                            $delta > 0 => '+'.number_format($delta, 2).' ★',
                            default => number_format($delta, 2).' ★',
                        };
                    })
                    ->placeholder('—')
                    ->color(function (Competitor $record): string {
                        $delta = $this->trendFor($record)['rating_delta'];

                        return match (true) {
                            $delta === null || abs($delta) < 0.005 => 'gray',
                            $delta > 0 => 'warning', // competitor improving
                            default => 'success',
                        };
                    }),

                TextColumn::make('spark')
                    ->label(__('pages/competitors.col_trend'))
                    ->state(fn (Competitor $record): ?HtmlString => $this->sparkSvg($this->trendFor($record)['spark']))
                    ->placeholder('—')
                    ->html(),

                TextColumn::make('location.name')
                    ->label(__('pages/competitors.col_location'))
                    ->limit(18)
                    ->tooltip(fn (Competitor $record): ?string => $record->location?->name)
                    ->visible(fn (): bool => Location::query()->count() > 1),

                TextColumn::make('last_checked_at')
                    ->label(__('pages/competitors.col_checked'))
                    ->since()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label(__('pages/competitors.col_location'))
                    ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label(__('pages/competitors.remove'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Competitor $record): void {
                        ActivityLogger::log('competitor.removed', ['name' => $record->name]);
                        $record->delete();
                        Notification::make()->title(__('pages/competitors.removed'))->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('add')
                    ->label(__('pages/competitors.add'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => $this->isConfigured())
                    ->modalHeading(__('pages/competitors.add_heading'))
                    ->schema([
                        Select::make('location_id')
                            ->label(__('pages/competitors.field_location'))
                            ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->default(fn (): mixed => Location::query()->orderBy('name')->value('id'))
                            ->required()
                            ->selectablePlaceholder(false),
                        Select::make('place_id')
                            ->label(__('pages/competitors.field_place'))
                            ->searchable()
                            ->required()
                            ->getSearchResultsUsing(function (string $search): array {
                                if (mb_strlen(trim($search)) < 3) {
                                    return [];
                                }

                                try {
                                    return collect(app(PlacesClient::class)->search($search))
                                        ->mapWithKeys(fn (array $place): array => [
                                            $place['place_id'] => $place['name'].($place['address'] ? ' — '.$place['address'] : ''),
                                        ])
                                        ->all();
                                } catch (Throwable) {
                                    return [];
                                }
                            })
                            // Async-search selects need a label resolver so the
                            // submitted value passes Filament's options check.
                            ->getOptionLabelUsing(function (mixed $value): string {
                                try {
                                    $place = app(PlacesClient::class)->details((string) $value);

                                    return $place['name'].($place['address'] ? ' — '.$place['address'] : '');
                                } catch (Throwable) {
                                    return (string) $value;
                                }
                            })
                            ->helperText(__('pages/competitors.field_place_helper')),
                    ])
                    ->action(fn (array $data) => $this->add($data)),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function add(array $data): void
    {
        try {
            $place = app(PlacesClient::class)->details((string) $data['place_id']);
        } catch (Throwable $e) {
            Notification::make()->title(__('pages/competitors.add_failed'))->body($e->getMessage())->danger()->send();

            return;
        }

        $competitor = Competitor::updateOrCreate(
            ['location_id' => (int) $data['location_id'], 'place_id' => $place['place_id']],
            [
                'name' => $place['name'],
                'address' => $place['address'],
                'rating' => $place['rating'],
                'reviews_count' => $place['reviews_count'],
                'last_checked_at' => now(),
            ],
        );

        app(CompetitorTrends::class)->record($competitor);

        ActivityLogger::log('competitor.added', ['name' => $place['name']]);

        Notification::make()->title(__('pages/competitors.added'))->success()->send();
    }

    /** "+0.3 ★ ahead" / "−12 reviews behind" style summary vs the own location. */
    protected function comparison(Competitor $record): string
    {
        $own = $record->location;

        if ($own === null || $own->rating === null || $record->rating === null) {
            return __('pages/competitors.vs_unknown');
        }

        $delta = round((float) $own->rating - (float) $record->rating, 1);

        if (abs($delta) < 0.05) {
            return __('pages/competitors.vs_tied');
        }

        return $delta > 0
            ? __('pages/competitors.vs_ahead', ['delta' => number_format(abs($delta), 1)])
            : __('pages/competitors.vs_behind', ['delta' => number_format(abs($delta), 1)]);
    }

    protected function comparisonColor(Competitor $record): string
    {
        $own = $record->location;

        if ($own === null || $own->rating === null || $record->rating === null) {
            return 'gray';
        }

        $delta = (float) $own->rating - (float) $record->rating;

        return match (true) {
            $delta > 0.05 => 'success',
            $delta < -0.05 => 'danger',
            default => 'gray',
        };
    }
}
