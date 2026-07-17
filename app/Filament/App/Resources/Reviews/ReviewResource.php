<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Reviews;

use App\Filament\App\Resources\Reviews\Pages\ListReviews;
use App\Filament\App\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Reviews';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'reviews';

    protected static ?string $recordTitleAttribute = 'author_name';

    // Isolation is at the DB level via stancl, not Filament native tenancy.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.reviews_all');
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // reviews are synced from the provider, never hand-created
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_reviews') ?? false;
    }

    /** Restrict to the user's allowed locations (null = all). */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('aiAgent');
        $ids = auth()->user()?->allowedLocationIds((string) session('current_workspace_id'));

        return $ids === null ? $query : $query->whereIn('location_id', $ids);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
