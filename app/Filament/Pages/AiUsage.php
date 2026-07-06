<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AiCreditLedger;
use App\Models\Workspace;
use App\Services\Ai\AiSpend;
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
 * Super-admin view of system-wide AI consumption: monthly cost/tokens, budget
 * progress, breakdowns per workspace/feature/model and the raw call log. Reads
 * the existing ai_credit_ledger — nothing extra is recorded.
 */
class AiUsage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $slug = 'ai-usage';

    protected string $view = 'filament.pages.ai-usage';

    public static function getNavigationLabel(): string
    {
        return 'AI usage';
    }

    public function getTitle(): string
    {
        return 'AI usage';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $spend = app(AiSpend::class);
        $stats = $spend->stats();
        $budget = $spend->budget();

        $byWorkspace = $spend->byWorkspace();
        $byDay = $spend->byDay(30);

        $workspaceNames = Workspace::query()
            ->whereIn('id', $byWorkspace->pluck('workspace_id'))
            ->pluck('name', 'id');

        return [
            'stats' => $stats,
            'budget' => $budget,
            'budgetPercent' => $budget !== null ? min(100, (int) round($stats['this_month'] / $budget * 100)) : null,
            'byWorkspace' => $byWorkspace->map(fn (object $row): array => [
                'name' => $workspaceNames[$row->workspace_id] ?? $row->workspace_id,
                'cost' => (float) $row->cost,
                'calls' => (int) $row->calls,
            ]),
            'byReason' => $spend->byReason(),
            'byModel' => $spend->byModel(),
            'byDay' => $byDay,
            'maxDay' => max(0.000001, ...array_column($byDay, 'cost')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AiCreditLedger::query()->with('workspace'))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No AI activity yet')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('workspace.name')
                    ->label('Workspace')
                    ->placeholder('—'),

                TextColumn::make('reason')
                    ->label('Feature')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('model')
                    ->label('Model')
                    ->placeholder('—')
                    ->fontFamily('mono'),

                TextColumn::make('input_tokens')
                    ->label('In')
                    ->numeric()
                    ->alignRight(),

                TextColumn::make('output_tokens')
                    ->label('Out')
                    ->numeric()
                    ->alignRight(),

                TextColumn::make('cost_usd')
                    ->label('Cost')
                    ->formatStateUsing(fn (?string $state): string => $state !== null && (float) $state > 0
                        ? '$'.number_format((float) $state, 4)
                        : '—')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('delta')
                    ->label('Credits')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? '—' : ($state > 0 ? '+'.$state : (string) $state))
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    })
                    ->alignRight(),
            ])
            ->filters([
                SelectFilter::make('workspace_id')
                    ->label('Workspace')
                    ->options(fn (): array => Workspace::query()->orderBy('name')->pluck('name', 'id')->all()),

                SelectFilter::make('reason')
                    ->label('Feature')
                    ->options(fn (): array => AiCreditLedger::query()
                        ->distinct()
                        ->pluck('reason', 'reason')
                        ->all()),

                SelectFilter::make('model')
                    ->label('Model')
                    ->options(fn (): array => AiCreditLedger::query()
                        ->whereNotNull('model')
                        ->distinct()
                        ->pluck('model', 'model')
                        ->all()),

                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                        DatePicker::make('until')->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $from) => $q->where('created_at', '>=', $from))
                        ->when($data['until'] ?? null, fn (Builder $q, string $until) => $q->where('created_at', '<=', $until.' 23:59:59'))),
            ]);
    }
}
