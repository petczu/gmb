<?php

declare(strict_types=1);

namespace App\Filament\App\Widgets;

use App\Models\Location;
use App\Models\Review;
use App\Support\DashboardPeriod;
use App\Support\DashboardWidgets;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestReviews extends TableWidget
{
    use Concerns\HasUserGridSpan;
    use Concerns\SurvivesBeingHidden;
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        // Hidden until the first location is connected — the dashboard shows
        // the connect-first empty state instead.
        return tenancy()->initialized
            && DashboardWidgets::visible('latest_reviews')
            && Location::query()->exists();
    }

    public function table(Table $table): Table
    {
        $period = DashboardPeriod::fromFilters($this->pageFilters);

        return $table
            ->heading(__('widgets.latest_reviews'))
            ->query(fn (): Builder => Review::query()
                ->with('latestQueueItem')
                ->when($period->locationIds !== [], fn (Builder $q): Builder => $q->whereIn('location_id', $period->locationIds))
                ->whereBetween('created_at_external', [$period->start, $period->end])
                ->latest('created_at_external')
                ->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('location.name')
                    ->label(__('widgets.col_location')),

                TextColumn::make('rating')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('author_name')->label(__('widgets.col_author')),

                TextColumn::make('text')
                    ->label(__('widgets.col_review'))
                    ->wrap()
                    ->limit(70)
                    ->placeholder(__('widgets.only_rating'))
                    ->state(fn (Review $record): ?string => $record->originalText()),

                TextColumn::make('created_at_external')
                    ->label(__('widgets.col_date'))
                    ->since(),

                TextColumn::make('reply_status')
                    ->label(__('widgets.col_reply'))
                    ->badge()
                    // ->state (not formatStateUsing) so the badge still renders
                    // when reply_status is null (not-yet-replied rows).
                    ->state(fn (Review $record): string => match (true) {
                        (bool) $record->reply_text => __('widgets.replied'),
                        $record->latestQueueItem?->status === 'scheduled' => __('widgets.scheduled'),
                        default => __('widgets.pending'),
                    })
                    ->color(fn (Review $record): string => match (true) {
                        (bool) $record->reply_text => 'success',
                        $record->latestQueueItem?->status === 'scheduled' => 'info',
                        default => 'gray',
                    }),
            ]);
    }
}
