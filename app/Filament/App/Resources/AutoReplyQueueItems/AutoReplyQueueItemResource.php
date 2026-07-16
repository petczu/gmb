<?php

namespace App\Filament\App\Resources\AutoReplyQueueItems;

use App\Filament\App\Resources\AutoReplyQueueItems\Pages\ListAutoReplyQueueItems;
use App\Filament\App\Resources\AutoReplyQueueItems\Tables\AutoReplyQueueItemsTable;
use App\Models\AutoReplyQueueItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AutoReplyQueueItemResource extends Resource
{
    protected static ?string $model = AutoReplyQueueItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|\UnitEnum|null $navigationGroup = 'Reviews';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'approvals';

    // Isolation is at the DB level via stancl, not Filament native tenancy.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('nav.approvals');
    }

    public static function getModelLabel(): string
    {
        return __('nav.approval_model');
    }

    public static function table(Table $table): Table
    {
        return AutoReplyQueueItemsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_reviews') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! tenancy()->initialized) {
            return null;
        }

        $count = AutoReplyQueueItem::query()->where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAutoReplyQueueItems::route('/'),
        ];
    }
}
