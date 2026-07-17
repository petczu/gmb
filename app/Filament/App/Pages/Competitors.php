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
use Filament\Forms\Components\TextInput;
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

    /**
     * One competitor's detail: rating, review count and (when DataForSEO
     * supplied it) the 1-5 star distribution as horizontal bars, alongside the
     * own side's rating for reference.
     */
    protected function competitorDetailHtml(Competitor $competitor): string
    {
        $html = '<div style="display:flex; flex-direction:column; gap:1rem;">';

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

        $html .= '</div></div>';

        return $html;
    }

    /** Move a competitor out of its group into its own single (unnamed) battle. */
    protected function ungroup(Competitor $competitor): void
    {
        $former = $competitor->battle;

        $battle = new CompetitorBattle;
        $battle->own_location_ids = Location::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $battle->save();

        $competitor->update(['battle_id' => $battle->id]);

        if ($former !== null) {
            $remaining = $former->competitors()->count();
            if ($remaining === 0) {
                $former->delete();
            } elseif ($remaining < 2) {
                // A "group" of one is just a normal competitor again.
                $former->update(['name' => null]);
            }
        }

        Notification::make()->title(__('pages/competitors.ungrouped'))->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Competitor::query()->with('battle')->whereNotNull('place_id'))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/competitors.empty'))
            ->emptyStateDescription(__('pages/competitors.empty_desc'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('pages/competitors.col_name'))
                    ->weight('medium')
                    ->description(function (Competitor $record): ?HtmlString {
                        $address = (string) $record->address;
                        if ($address === '') {
                            return null;
                        }

                        return new HtmlString('<span title="'.e($address).'">'.e(Str::limit($address, 52)).'</span>');
                    }),

                TextColumn::make('group')
                    ->label(__('pages/competitors.col_group'))
                    ->badge()
                    ->color('primary')
                    ->placeholder('—')
                    ->state(fn (Competitor $record): ?string => $record->battle !== null && filled($record->battle->name)
                        ? (string) $record->battle->name
                        : null),

                TextColumn::make('rating')
                    ->label(__('pages/competitors.col_rating'))
                    ->state(fn (Competitor $record): string => $record->rating !== null ? number_format((float) $record->rating, 1).' ★' : '—'),

                TextColumn::make('reviews_count')
                    ->label(__('pages/competitors.col_reviews'))
                    ->state(fn (Competitor $record): string => number_format((int) $record->reviews_count)),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label(__('pages/competitors.view'))
                        ->icon(Heroicon::OutlinedChartBar)
                        ->modalHeading(fn (Competitor $record): string => (string) $record->name)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('pages/competitors.close'))
                        ->schema(fn (Competitor $record): array => [
                            Placeholder::make('competitor_details')
                                ->hiddenLabel()
                                ->content(new HtmlString($this->competitorDetailHtml($record))),
                        ]),

                    Action::make('ungroup')
                        ->label(__('pages/competitors.ungroup'))
                        ->icon(Heroicon::OutlinedArrowUturnLeft)
                        ->visible(fn (Competitor $record): bool => $record->battle !== null && filled($record->battle->name))
                        ->action(fn (Competitor $record) => $this->ungroup($record)),

                    Action::make('remove')
                        ->label(__('pages/competitors.remove'))
                        ->icon(Heroicon::OutlinedTrash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Competitor $record): void {
                            $battle = $record->battle;
                            ActivityLogger::log('competitor.removed', ['name' => (string) $record->name]);
                            $record->delete();
                            if ($battle !== null && $battle->competitors()->count() === 0) {
                                $battle->delete();
                            }
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

                Action::make('create_group')
                    ->label(__('pages/competitors.create_group'))
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->color('gray')
                    ->visible(fn (): bool => $this->isConfigured() && Competitor::query()->count() >= 2)
                    ->modalHeading(__('pages/competitors.group_heading'))
                    ->schema($this->groupFormSchema())
                    ->action(fn (array $data) => $this->createGroup($data)),
            ]);
    }

    /** Group form: name plus the tracked competitors to combine into it. */
    protected function groupFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('pages/competitors.field_group_name'))
                ->required()
                ->maxLength(60),

            Select::make('competitor_ids')
                ->label(__('pages/competitors.field_group_competitors'))
                ->multiple()
                ->required()
                ->minItems(2)
                ->options(fn (): array => Competitor::query()->orderBy('name')->pluck('name', 'id')->all())
                ->helperText(__('pages/competitors.field_group_competitors_helper')),
        ];
    }

    /**
     * Combine several tracked competitors into one named group by moving them
     * into a fresh battle; any single-competitor battles left empty by the move
     * are removed. The chart then draws the group as one summed line.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createGroup(array $data): void
    {
        $ids = array_values(array_filter(array_map('intval', (array) ($data['competitor_ids'] ?? []))));
        $competitors = Competitor::query()->whereIn('id', $ids)->get();
        if ($competitors->count() < 2) {
            Notification::make()->title(__('pages/competitors.group_need_two'))->warning()->send();

            return;
        }

        $formerBattleIds = $competitors->pluck('battle_id')->filter()->unique()->all();
        $ownIds = Location::query()->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $battle = new CompetitorBattle;
        $battle->name = trim((string) ($data['name'] ?? ''));
        $battle->own_location_ids = $ownIds;
        $battle->save();

        Competitor::query()->whereIn('id', $ids)->update(['battle_id' => $battle->id]);

        // Drop the now-empty single battles the competitors used to live in.
        CompetitorBattle::query()
            ->whereIn('id', $formerBattleIds)
            ->whereDoesntHave('competitors')
            ->delete();

        ActivityLogger::log('competitor.group_created', ['name' => (string) $battle->name]);

        Notification::make()->title(__('pages/competitors.group_created'))->success()->send();
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
