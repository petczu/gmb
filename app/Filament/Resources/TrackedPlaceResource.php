<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TrackedPlaceResource\Pages;
use App\Models\PlaceSnapshot;
use App\Models\TrackedPlace;
use App\Services\Competitors\CompetitorTrends;
use App\Services\Competitors\PlacesClient;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

/**
 * Admin watchlist of Google places (super-admin panel). Snapshots for these
 * places are collected by competitors:refresh even before any workspace
 * tracks them, so a client adding such a competitor later starts with
 * history instead of "collecting…".
 */
class TrackedPlaceResource extends Resource
{
    protected static ?string $model = TrackedPlace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Tracked places';

    protected static ?string $modelLabel = 'tracked place';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('place_id')
                ->label('Google place')
                ->searchable()
                ->required()
                ->unique(ignoreRecord: true)
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
                ->getOptionLabelUsing(function ($value): string {
                    try {
                        $details = app(PlacesClient::class)->details((string) $value);

                        return (string) ($details['name'] ?? $value);
                    } catch (Throwable) {
                        return (string) $value;
                    }
                })
                ->helperText('Snapshots are collected daily even before any workspace tracks this place.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('address')->searchable()->limit(50),
                TextColumn::make('place_id')->label('Place id')->copyable()->limit(24),
                TextColumn::make('snapshots')
                    ->label('Snapshots')
                    ->state(fn (TrackedPlace $record): int => PlaceSnapshot::query()->where('place_id', $record->place_id)->count()),
                TextColumn::make('created_at')->label('Added')->since(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Fill name/address from Places and record the first snapshot right away.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enrich(array $data): array
    {
        $details = app(PlacesClient::class)->details((string) $data['place_id']);

        $data['name'] = (string) ($details['name'] ?? $data['place_id']);
        $data['address'] = $details['address'] ?? null;

        CompetitorTrends::recordPlace(
            (string) $data['place_id'],
            $details['rating'] !== null ? (float) $details['rating'] : null,
            (int) ($details['reviews_count'] ?? 0),
        );

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTrackedPlaces::route('/'),
        ];
    }
}
