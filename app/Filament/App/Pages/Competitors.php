<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Competitor;
use App\Models\CompetitorBattle;
use App\Models\Location;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use App\Support\DashboardPeriod;
use App\Support\Sparkline;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

/**
 * Competitor benchmark, organised as named "battles": a group of the
 * workspace's own locations compared against a group of competitor places.
 * Ratings aggregate weighted by review count. Daily snapshots
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

    public string $trendPeriod = 'last_30';

    public ?string $trendFrom = null;

    public ?string $trendTo = null;

    /** @var array<int, array{reviews_delta: ?int, rating_delta: ?float, prev_reviews_delta: ?int, spark: list<int>}> */
    private array $trendCache = [];

    /** @var array<int, int> own new reviews per battle for the window */
    private array $ownCache = [];

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
     * @return array{reviews_delta: ?int, rating_delta: ?float, prev_reviews_delta: ?int, spark: list<int>}
     */
    protected function trendFor(CompetitorBattle $battle): array
    {
        return $this->trendCache[$battle->id]
            ??= app(CompetitorTrends::class)->placesSummary(
                $battle->competitors->pluck('place_id')->filter()->values()->all(),
                $this->trendStart(),
                $this->trendEnd(),
            );
    }

    protected function ownNewReviews(CompetitorBattle $battle): int
    {
        return $this->ownCache[$battle->id]
            ??= app(CompetitorTrends::class)->ownNewReviewsForMany($battle->ownLocationIds(), $this->trendStart(), $this->trendEnd());
    }

    /** Weighted competitor rating for the battle (by review count). */
    protected function competitorRating(CompetitorBattle $battle): ?float
    {
        return CompetitorTrends::weightedRating(
            $battle->competitors->map(fn (Competitor $c): array => [
                'rating' => $c->rating !== null ? (float) $c->rating : null,
                'reviews_count' => (int) $c->reviews_count,
            ]),
        );
    }

    /** Weighted own rating across the battle's own locations. */
    protected function ownRating(CompetitorBattle $battle): ?float
    {
        return CompetitorTrends::weightedRating(
            $battle->ownLocations()->map(fn (Location $l): array => [
                'rating' => $l->rating !== null ? (float) $l->rating : null,
                'reviews_count' => (int) $l->reviews_count,
            ]),
        );
    }

    protected function competitorReviews(CompetitorBattle $battle): int
    {
        return (int) $battle->competitors->sum('reviews_count');
    }

    /** Tiny inline sparkline of the aggregated review-count snapshots. */
    protected function sparkSvg(array $values): ?HtmlString
    {
        return Sparkline::svg($values);
    }

    protected function battleName(CompetitorBattle $battle): string
    {
        return $battle->displayName();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CompetitorBattle::query()->with('competitors'))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/competitors.empty'))
            ->emptyStateDescription(__('pages/competitors.empty_desc'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('pages/competitors.col_battle'))
                    ->weight('medium')
                    ->state(fn (CompetitorBattle $record): string => Str::limit($this->battleName($record), 38))
                    ->tooltip(fn (CompetitorBattle $record): ?string => mb_strlen($this->battleName($record)) > 38 ? $this->battleName($record) : null)
                    ->description(function (CompetitorBattle $record): ?HtmlString {
                        $names = $record->competitors->pluck('name')->filter()->implode(', ');
                        if ($names === '') {
                            return null;
                        }

                        return new HtmlString('<span title="'.e($names).'">'.e(Str::limit($names, 52)).'</span>');
                    }),

                TextColumn::make('rating')
                    ->label(__('pages/competitors.col_rating'))
                    ->state(fn (CompetitorBattle $record): string => ($r = $this->competitorRating($record)) !== null ? number_format($r, 1).' ★' : '—')
                    ->tooltip(__('pages/competitors.rating_weighted_hint')),

                TextColumn::make('reviews')
                    ->label(__('pages/competitors.col_reviews'))
                    ->state(fn (CompetitorBattle $record): string => number_format($this->competitorReviews($record))),

                TextColumn::make('vs')
                    ->label(__('pages/competitors.col_vs'))
                    ->badge()
                    ->state(fn (CompetitorBattle $record): string => $this->comparison($record))
                    ->color(fn (CompetitorBattle $record): string => $this->comparisonColor($record)),

                TextColumn::make('new_reviews')
                    ->label(__('pages/competitors.col_new_reviews'))
                    ->state(function (CompetitorBattle $record): string {
                        $delta = $this->trendFor($record)['reviews_delta'];

                        return $delta === null
                            ? __('pages/competitors.collecting')
                            : ($delta > 0 ? '+'.$delta : (string) $delta);
                    })
                    ->description(function (CompetitorBattle $record): ?string {
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
                    ->color(function (CompetitorBattle $record): string {
                        $delta = $this->trendFor($record)['reviews_delta'];

                        return $delta === null ? 'gray' : ($this->ownNewReviews($record) >= $delta ? 'success' : 'danger');
                    })
                    ->tooltip(__('pages/competitors.trend_hint')),

                TextColumn::make('rating_trend')
                    ->label(__('pages/competitors.col_rating_trend'))
                    ->state(function (CompetitorBattle $record): ?string {
                        $delta = $this->trendFor($record)['rating_delta'];

                        return match (true) {
                            $delta === null => null,
                            abs($delta) < 0.005 => __('pages/competitors.no_change'),
                            $delta > 0 => '+'.number_format($delta, 2).' ★',
                            default => number_format($delta, 2).' ★',
                        };
                    })
                    ->placeholder('—')
                    ->color(function (CompetitorBattle $record): string {
                        $delta = $this->trendFor($record)['rating_delta'];

                        return match (true) {
                            $delta === null || abs($delta) < 0.005 => 'gray',
                            $delta > 0 => 'warning',
                            default => 'success',
                        };
                    }),

                TextColumn::make('spark')
                    ->label(__('pages/competitors.col_trend'))
                    ->state(fn (CompetitorBattle $record): ?HtmlString => $this->sparkSvg($this->trendFor($record)['spark']))
                    ->placeholder('—')
                    ->html(),

                TextColumn::make('own')
                    ->label(__('pages/competitors.col_location'))
                    ->state(function (CompetitorBattle $record): string {
                        $names = $record->ownLocations()->pluck('name')->implode(', ');

                        return $names === '' ? '—' : Str::limit($names, 24);
                    })
                    ->tooltip(fn (CompetitorBattle $record): ?string => $record->ownLocations()->pluck('name')->implode(', ') ?: null),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label(__('pages/competitors.edit'))
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (): bool => $this->isConfigured())
                        ->modalHeading(__('pages/competitors.edit_heading'))
                        ->fillForm(fn (CompetitorBattle $record): array => [
                            'name' => $record->name,
                            'own_location_ids' => $record->ownLocationIds(),
                            'place_ids' => $record->competitors->pluck('place_id')->all(),
                        ])
                        ->schema($this->battleFormSchema())
                        ->action(fn (array $data, CompetitorBattle $record) => $this->save($data, $record)),

                    Action::make('remove')
                        ->label(__('pages/competitors.remove'))
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (CompetitorBattle $record): void {
                            ActivityLogger::log('competitor.battle_removed', ['name' => $this->battleName($record)]);
                            $record->competitors()->delete();
                            $record->delete();
                            Notification::make()->title(__('pages/competitors.removed'))->success()->send();
                        }),
                ]),
            ])
            ->headerActions([
                Action::make('add')
                    ->label(__('pages/competitors.add'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => $this->isConfigured())
                    ->modalHeading(__('pages/competitors.add_heading'))
                    ->schema($this->battleFormSchema())
                    ->action(fn (array $data) => $this->save($data, null)),
            ]);
    }

    /** Shared add/edit form: name + own locations (multi) + competitor places (multi). */
    protected function battleFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('pages/competitors.field_name'))
                ->maxLength(120)
                ->placeholder(__('pages/competitors.field_name_placeholder')),

            Select::make('own_location_ids')
                ->label(__('pages/competitors.field_your_locations'))
                ->multiple()
                ->required()
                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default(fn (): array => Location::query()->count() === 1
                    ? [(int) Location::query()->value('id')]
                    : [])
                ->helperText(__('pages/competitors.field_your_locations_helper')),

            Select::make('place_ids')
                ->label(__('pages/competitors.field_places'))
                ->multiple()
                ->required()
                ->searchable()
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
                // Resolve labels for already-selected place ids. Prefer the stored
                // competitor name (no API call); fall back to a Places lookup.
                ->getOptionLabelsUsing(function (array $values): array {
                    $stored = Competitor::query()->whereIn('place_id', $values)->pluck('name', 'place_id')->all();

                    return collect($values)->mapWithKeys(function (string $value) use ($stored): array {
                        if (isset($stored[$value])) {
                            return [$value => $stored[$value]];
                        }

                        try {
                            $place = app(PlacesClient::class)->details($value);

                            return [$value => $place['name'].($place['address'] ? ' — '.$place['address'] : '')];
                        } catch (Throwable) {
                            return [$value => $value];
                        }
                    })->all();
                })
                ->helperText(__('pages/competitors.field_places_helper')),
        ];
    }

    /**
     * Create or update a battle and reconcile its competitor places.
     *
     * @param  array<string, mixed>  $data
     */
    protected function save(array $data, ?CompetitorBattle $battle): void
    {
        $ownIds = array_values(array_map('intval', (array) ($data['own_location_ids'] ?? [])));
        $placeIds = array_values(array_unique(array_filter((array) ($data['place_ids'] ?? []))));

        $battle ??= new CompetitorBattle;
        $battle->name = filled($data['name'] ?? null) ? trim((string) $data['name']) : null;
        $battle->own_location_ids = $ownIds;
        $battle->save();

        $primaryLocationId = $ownIds[0] ?? null;
        $failed = [];

        foreach ($placeIds as $placeId) {
            try {
                $place = app(PlacesClient::class)->details((string) $placeId);
            } catch (Throwable) {
                $failed[] = $placeId;

                continue;
            }

            $competitor = Competitor::updateOrCreate(
                ['battle_id' => $battle->id, 'place_id' => $place['place_id']],
                [
                    'location_id' => $primaryLocationId,
                    'name' => $place['name'],
                    'address' => $place['address'],
                    'rating' => $place['rating'],
                    'reviews_count' => $place['reviews_count'],
                    'last_checked_at' => now(),
                ],
            );

            app(CompetitorTrends::class)->record($competitor);
        }

        // Drop places removed from the selection; keep the primary location in sync.
        $battle->competitors()->whereNotIn('place_id', $placeIds)->delete();
        $battle->competitors()->update(['location_id' => $primaryLocationId]);

        if ($failed !== []) {
            Notification::make()->title(__('pages/competitors.some_failed', ['count' => count($failed)]))->warning()->send();
        }

        ActivityLogger::log('competitor.battle_saved', ['name' => $this->battleName($battle->fresh('competitors'))]);

        Notification::make()->title(__('pages/competitors.saved'))->success()->send();
    }

    /** Weighted own rating vs weighted competitor rating for the battle. */
    protected function comparison(CompetitorBattle $battle): string
    {
        $own = $this->ownRating($battle);
        $competitor = $this->competitorRating($battle);

        if ($own === null || $competitor === null) {
            return __('pages/competitors.vs_unknown');
        }

        $delta = round($own - $competitor, 1);

        if (abs($delta) < 0.05) {
            return __('pages/competitors.vs_tied');
        }

        return $delta > 0
            ? __('pages/competitors.vs_ahead', ['delta' => number_format(abs($delta), 1)])
            : __('pages/competitors.vs_behind', ['delta' => number_format(abs($delta), 1)]);
    }

    protected function comparisonColor(CompetitorBattle $battle): string
    {
        $own = $this->ownRating($battle);
        $competitor = $this->competitorRating($battle);

        if ($own === null || $competitor === null) {
            return 'gray';
        }

        $delta = $own - $competitor;

        return match (true) {
            $delta > 0.05 => 'success',
            $delta < -0.05 => 'danger',
            default => 'gray',
        };
    }
}
