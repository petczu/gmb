<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\AiCreditLedger;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
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

class Credits extends Page implements HasTable
{
    use InteractsWithTable;

    /** Ledger reasons per feature bucket (the Feature filter + badge). */
    private const FEATURES = [
        'reviews' => ['auto_reply', 'manual_reply'],
        'reports' => ['report', 'instruction_improve'],
        'agents' => ['agent_description'],
        'billing' => ['pack', 'topup', 'adjustment'],
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'credits';

    protected string $view = 'filament.app.pages.credits';

    /** @var array<int, string|null> location name per referenced review id */
    private array $locationByReview = [];

    public static function getNavigationLabel(): string
    {
        return __('nav.credits');
    }

    public function getTitle(): string
    {
        return __('pages/credits.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_billing') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_billing') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    /**
     * @return array<string, int>
     */
    protected function getViewData(): array
    {
        $workspace = $this->workspace();
        $credits = app(AiCreditService::class);

        return [
            'balance' => $credits->balance($workspace),
            'spentThisMonth' => $credits->spentThisMonth($workspace),
            'totalUsed' => abs((int) AiCreditLedger::query()
                ->where('workspace_id', $workspace->id)
                ->where('delta', '<', 0)
                ->sum('delta')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AiCreditLedger::query()->where('workspace_id', (string) session('current_workspace_id')))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/credits.empty'))
            ->columns([
                TextColumn::make('delta')
                    ->label(__('pages/credits.col_amount'))
                    ->formatStateUsing(fn (int $state): string => match (true) {
                        $state > 0 => '+'.number_format($state).' '.trans_choice('pages/credits.credits_word', $state),
                        $state < 0 => number_format($state).' '.trans_choice('pages/credits.credits_word', abs($state)),
                        default => __('pages/credits.included_in_plan'),
                    })
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    })
                    ->weight('medium'),

                TextColumn::make('reason')
                    ->label(__('pages/credits.col_description'))
                    ->formatStateUsing(fn (string $state): string => __('pages/credits.desc_'.$state))
                    ->tooltip(fn (AiCreditLedger $record): ?string => $record->model === null ? null : sprintf(
                        '%s · %s → %s tokens',
                        $record->model,
                        number_format((int) $record->input_tokens),
                        number_format((int) $record->output_tokens),
                    )),

                TextColumn::make('created_at')
                    ->label(__('pages/credits.col_date'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('feature')
                    ->label(__('pages/credits.col_feature'))
                    ->badge()
                    ->state(fn (AiCreditLedger $record): string => __('pages/credits.feature_'.$this->featureOf($record->reason)))
                    ->color(fn (AiCreditLedger $record): string => match ($this->featureOf($record->reason)) {
                        'reviews' => 'info',
                        'reports' => 'warning',
                        'agents' => 'gray',
                        default => 'success',
                    }),

                TextColumn::make('listing')
                    ->label(__('pages/credits.col_listing'))
                    ->state(fn (AiCreditLedger $record): ?string => $this->listingOf($record))
                    ->placeholder('—'),

                TextColumn::make('balance_after')
                    ->label(__('pages/credits.col_balance'))
                    ->numeric()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('feature')
                    ->label(__('pages/credits.col_feature'))
                    ->options(collect(self::FEATURES)->mapWithKeys(
                        fn (array $reasons, string $key): array => [$key => __('pages/credits.feature_'.$key)],
                    )->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereIn('reason', self::FEATURES[$data['value']] ?? [])
                        : $query),

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

    private function featureOf(string $reason): string
    {
        foreach (self::FEATURES as $feature => $reasons) {
            if (in_array($reason, $reasons, true)) {
                return $feature;
            }
        }

        return 'billing';
    }

    /** Location name for rows that reference a review (tenant DB lookup, memoized). */
    private function listingOf(AiCreditLedger $record): ?string
    {
        if ($record->ref_type !== 'review' || $record->ref_id === null || ! tenancy()->initialized) {
            return null;
        }

        $reviewId = (int) $record->ref_id;

        if (! array_key_exists($reviewId, $this->locationByReview)) {
            $this->locationByReview[$reviewId] = Review::query()
                ->with('location')
                ->find($reviewId)
                ?->location?->name;
        }

        return $this->locationByReview[$reviewId];
    }
}
