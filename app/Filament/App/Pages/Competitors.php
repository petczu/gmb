<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Competitor;
use App\Models\CompetitorBattle;
use App\Models\Location;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

/**
 * Competitor benchmark: a flat list of tracked competitor places with their
 * Google rating and review count. Backed by the competitor_battles model
 * (each row is a battle scoped to all own locations, so the dashboard growth
 * chart still has a "You" side), but the page surfaces no head-to-head
 * comparison and no trends — you just add competitors, view details, or
 * remove them. Growth over time lives on the dashboard chart.
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

    protected function battleName(CompetitorBattle $battle): string
    {
        return $battle->displayName();
    }

    /**
     * Per-competitor detail: rating, review count and (when DataForSEO
     * supplied it) the 1-5 star distribution as horizontal bars, alongside
     * the own side's rating for reference.
     */
    protected function competitorDetailsHtml(CompetitorBattle $battle): string
    {
        $ownRating = $this->ownRating($battle);
        $html = '<div style="display:flex; flex-direction:column; gap:1rem;">';

        // Own side header row.
        $html .= '<div style="display:flex; align-items:center; justify-content:space-between; padding:.6rem .8rem; border-radius:.6rem; background:rgb(45 25 236 / .06);">'
            .'<span style="font-weight:700;">'.e($battle->ownLocations()->pluck('name')->implode(', ') ?: __('pages/competitors.you')).'</span>'
            .'<span style="font-weight:700;">'.($ownRating !== null ? number_format($ownRating, 1).' ★' : '—').'</span>'
            .'</div>';

        foreach ($battle->competitors as $competitor) {
            $rating = $competitor->rating !== null ? number_format((float) $competitor->rating, 1).' ★' : '—';
            $reviews = trans_choice('pages/competitors.reviews_count', (int) $competitor->reviews_count, ['count' => number_format((int) $competitor->reviews_count)]);

            $html .= '<div style="border:1px solid rgb(0 0 0 / .08); border-radius:.6rem; padding:.7rem .85rem;">';
            $html .= '<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.5rem;">'
                .'<span style="font-weight:600; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">'.e((string) $competitor->name).'</span>'
                .'<span style="white-space:nowrap; color:#6b7280; font-size:.85rem;">'.e($rating).' · '.e($reviews).'</span>'
                .'</div>';

            $dist = $competitor->rating_distribution;
            if (is_array($dist) && array_sum($dist) > 0) {
                $max = max($dist);
                $colors = [5 => '#16a34a', 4 => '#84cc16', 3 => '#eab308', 2 => '#f97316', 1 => '#dc2626'];
                for ($star = 5; $star >= 1; $star--) {
                    $count = (int) ($dist[$star] ?? 0);
                    $pct = $max > 0 ? round($count / $max * 100) : 0;
                    $html .= '<div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.25rem;">'
                        .'<span style="width:1.6rem; font-size:.75rem; color:#6b7280; text-align:right;">'.$star.'★</span>'
                        .'<span style="flex:1; height:.55rem; border-radius:999px; background:rgb(0 0 0 / .06); overflow:hidden;">'
                        .'<span style="display:block; height:100%; width:'.$pct.'%; background:'.$colors[$star].';"></span></span>'
                        .'<span style="width:3rem; font-size:.75rem; color:#6b7280;">'.number_format($count).'</span>'
                        .'</div>';
                }
            } else {
                $html .= '<div style="font-size:.8rem; color:#9ca3af;">'.e(__('pages/competitors.no_distribution')).'</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => CompetitorBattle::query()->has('competitors')->with('competitors'))
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
                        $addresses = $record->competitors->pluck('address')->filter()->implode(' · ');
                        if ($addresses === '') {
                            return null;
                        }

                        return new HtmlString('<span title="'.e($addresses).'">'.e(Str::limit($addresses, 52)).'</span>');
                    }),

                TextColumn::make('rating')
                    ->label(__('pages/competitors.col_rating'))
                    ->state(fn (CompetitorBattle $record): string => ($r = $this->competitorRating($record)) !== null ? number_format($r, 1).' ★' : '—')
                    ->tooltip(__('pages/competitors.rating_weighted_hint')),

                TextColumn::make('reviews')
                    ->label(__('pages/competitors.col_reviews'))
                    ->state(fn (CompetitorBattle $record): string => number_format($this->competitorReviews($record))),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label(__('pages/competitors.view'))
                        ->icon(Heroicon::OutlinedChartBar)
                        ->modalHeading(fn (CompetitorBattle $record): string => $this->battleName($record))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('pages/competitors.close'))
                        ->schema(fn (CompetitorBattle $record): array => [
                            Placeholder::make('competitor_details')
                                ->hiddenLabel()
                                ->content(new HtmlString($this->competitorDetailsHtml($record))),
                        ]),

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
                    ->action(fn (array $data) => $this->save($data)),
            ]);
    }

    /** Add form: one competitor place at a time (search Google Places). */
    protected function battleFormSchema(): array
    {
        return [
            Select::make('place_id')
                ->label(__('pages/competitors.field_place'))
                ->required()
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    if (mb_strlen(trim($search)) < 3) {
                        return [];
                    }

                    // Hide places we already track so they can't be added twice.
                    $tracked = Competitor::query()->pluck('place_id')->all();

                    try {
                        return collect(app(PlacesClient::class)->search($search))
                            ->reject(fn (array $place): bool => in_array($place['place_id'], $tracked, true))
                            ->mapWithKeys(fn (array $place): array => [
                                $place['place_id'] => $place['name'].($place['address'] ? ' — '.$place['address'] : ''),
                            ])
                            ->all();
                    } catch (Throwable $e) {
                        // A silent empty list reads as "no results"; surface
                        // the real cause (quota, billing, key restrictions).
                        Log::warning('Places competitor search failed', ['error' => $e->getMessage()]);

                        Notification::make()
                            ->title(__('pages/competitors.search_failed'))
                            ->body(Str::limit($e->getMessage(), 200))
                            ->danger()
                            ->send();

                        return [];
                    }
                })
                ->getOptionLabelUsing(function (string $value): string {
                    try {
                        $place = app(PlacesClient::class)->details($value);

                        return $place['name'].($place['address'] ? ' — '.$place['address'] : '');
                    } catch (Throwable) {
                        return $value;
                    }
                })
                ->helperText(__('pages/competitors.field_places_helper')),
        ];
    }

    /**
     * Add one competitor place. Own locations are auto-scoped to ALL of the
     * workspace's locations (keeps the dashboard "You vs competitors" growth
     * chart working); the name is derived from the place.
     *
     * @param  array<string, mixed>  $data
     */
    protected function save(array $data): void
    {
        $placeId = trim((string) ($data['place_id'] ?? ''));
        if ($placeId === '') {
            return;
        }

        // Already tracked → error, don't create a stray empty row.
        if (Competitor::query()->where('place_id', $placeId)->exists()) {
            Notification::make()->title(__('pages/competitors.already_tracked'))->warning()->send();

            return;
        }

        try {
            $place = app(PlacesClient::class)->details($placeId);
        } catch (Throwable $e) {
            Notification::make()
                ->title(__('pages/competitors.search_failed'))
                ->body(Str::limit($e->getMessage(), 200))
                ->danger()
                ->send();

            return;
        }

        $ownIds = Location::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        // Only create the battle once we have a valid, non-duplicate place, so a
        // failure never leaves an empty "Untitled competition" behind.
        $battle = new CompetitorBattle;
        $battle->own_location_ids = $ownIds;
        $battle->save();

        $competitor = Competitor::create([
            'battle_id' => $battle->id,
            'place_id' => $place['place_id'],
            'location_id' => $ownIds[0] ?? null,
            'name' => $place['name'],
            'address' => $place['address'],
            'rating' => $place['rating'],
            'reviews_count' => $place['reviews_count'],
            'last_checked_at' => now(),
        ]);

        app(CompetitorTrends::class)->record($competitor);

        ActivityLogger::log('competitor.added', ['name' => (string) $place['name']]);

        Notification::make()->title(__('pages/competitors.saved'))->success()->send();
    }
}
