<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\ActivityEntry;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The workspace's activity feed: who did what, when. Entries are appended by
 * ActivityLogger at the feature sites (replies, reports, team, keys, ...).
 */
class Activity extends Page implements HasTable
{
    use InteractsWithTable;

    /** Action prefixes per category (the Category filter + badge). */
    private const CATEGORIES = [
        'reviews' => ['reply.', 'review_page.'],
        'posts' => ['post.'],
        'reports' => ['report.', 'schedule.'],
        'team' => ['team.'],
        'locations' => ['location.', 'listing.', 'competitor.'],
        'integrations' => ['apikey.', 'webhook.'],
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 84;

    protected static ?string $slug = 'activity';

    protected string $view = 'filament.app.pages.activity';

    public static function getNavigationLabel(): string
    {
        return __('pages/activity.nav');
    }

    public function getTitle(): string
    {
        return __('pages/activity.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_team') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_team') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ActivityEntry::query())
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/activity.empty'))
            ->emptyStateDescription(__('pages/activity.empty_desc'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('pages/activity.col_when'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('user_name')
                    ->label(__('pages/activity.col_who'))
                    ->placeholder(__('pages/activity.system'))
                    ->weight('medium'),

                TextColumn::make('action')
                    ->label(__('pages/activity.col_what'))
                    ->formatStateUsing(fn (string $state, ActivityEntry $record): string => $this->describe($record))
                    ->wrap(),

                TextColumn::make('category')
                    ->label(__('pages/activity.col_category'))
                    ->badge()
                    ->state(fn (ActivityEntry $record): string => __('pages/activity.cat_'.$this->categoryOf($record->action)))
                    ->color(fn (ActivityEntry $record): string => match ($this->categoryOf($record->action)) {
                        'reviews' => 'info',
                        'posts' => 'danger',
                        'reports' => 'warning',
                        'team' => 'success',
                        'locations' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(__('pages/activity.col_category'))
                    ->options(collect(self::CATEGORIES)->mapWithKeys(
                        fn (array $prefixes, string $key): array => [$key => __('pages/activity.cat_'.$key)],
                    )->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where(function (Builder $q) use ($data): void {
                            foreach (self::CATEGORIES[$data['value']] ?? [] as $prefix) {
                                $q->orWhere('action', 'like', $prefix.'%');
                            }
                        })
                        : $query),

                SelectFilter::make('user_id')
                    ->label(__('pages/activity.col_who'))
                    ->options(fn (): array => ActivityEntry::query()
                        ->whereNotNull('user_id')
                        ->distinct()
                        ->pluck('user_name', 'user_id')
                        ->filter()
                        ->all()),

                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')->label(__('common.from'))->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                        DatePicker::make('until')->label(__('common.to'))->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $from) => $q->where('created_at', '>=', $from))
                        ->when($data['until'] ?? null, fn (Builder $q, string $until) => $q->where('created_at', '<=', $until.' 23:59:59'))),
            ]);
    }

    /** Human sentence for one entry: the action's lang line with meta values. */
    private function describe(ActivityEntry $record): string
    {
        $key = 'pages/activity.action_'.str_replace('.', '_', $record->action);
        $meta = collect($record->meta ?? [])
            ->map(fn (mixed $v): string => is_array($v) ? implode(', ', $v) : ((string) $v ?: '—'))
            ->all();

        $line = __($key, $meta);

        // Unknown/legacy action keys fall back to the raw action name.
        return $line === $key ? $record->action : $line;
    }

    private function categoryOf(string $action): string
    {
        foreach (self::CATEGORIES as $category => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($action, $prefix)) {
                    return $category;
                }
            }
        }

        return 'integrations';
    }
}
