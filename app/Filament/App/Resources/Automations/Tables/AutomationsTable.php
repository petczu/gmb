<?php

namespace App\Filament\App\Resources\Automations\Tables;

use App\Filament\App\Resources\Automations\AutomationResource;
use App\Models\Automation;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AutomationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(Automation::query()->exists())
            ->emptyStateIcon(Heroicon::OutlinedBolt)
            ->emptyStateHeading(__('resources/automations.empty_heading'))
            ->emptyStateDescription(__('resources/automations.empty_desc'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('resources/automations.empty_cta'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => AutomationResource::getUrl('create')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('enabled')
                    ->boolean(),

                TextColumn::make('rating_filter')
                    ->label(__('resources/automations.col_rating'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? $state.'★' : __('resources/automations.rating_any'))
                    ->placeholder(__('resources/automations.rating_any'))
                    ->visibleFrom('md'),

                TextColumn::make('content_type')
                    ->label(__('resources/automations.col_reply'))
                    ->formatStateUsing(fn (Automation $record): string => $record->content_type === 'ai_agent'
                        ? __('resources/automations.reply_ai', ['agent' => $record->aiAgent?->name ?? '—'])
                        : __('resources/automations.reply_default'))
                    ->visibleFrom('lg'),

                TextColumn::make('approve_before_posting')
                    ->label(__('resources/automations.col_mode'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('resources/automations.mode_approval') : __('resources/automations.mode_auto'))
                    ->color(fn (bool $state): string => $state ? 'warning' : 'success')
                    ->visibleFrom('md'),

                TextColumn::make('all_locations')
                    ->label(__('resources/automations.col_scope'))
                    ->formatStateUsing(fn (Automation $record): string => $record->all_locations
                        ? __('resources/automations.scope_all')
                        : __('resources/automations.scope_count', ['count' => count($record->location_ids ?? [])]))
                    ->visibleFrom('lg'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('run')
                        ->label(__('resources/automations.run_now'))
                        ->icon(Heroicon::OutlinedPlay)
                        ->requiresConfirmation()
                        ->modalHeading(__('resources/automations.run_heading'))
                        ->modalDescription(__('resources/automations.run_desc'))
                        ->schema([
                            Grid::make(2)->schema([
                                DatePicker::make('from')
                                    ->label(__('resources/automations.run_from'))
                                    ->native(false)
                                    ->maxDate(now()),
                                DatePicker::make('until')
                                    ->label(__('resources/automations.run_until'))
                                    ->native(false)
                                    ->maxDate(now()),
                            ]),
                        ])
                        ->visible(fn (Automation $record): bool => $record->enabled)
                        ->action(function (Automation $record, array $data): void {
                            $workspace = Workspace::findOrFail(session('current_workspace_id'));

                            $stats = app(AutomationService::class)->processAutomation(
                                $workspace,
                                $record,
                                filled($data['from'] ?? null) ? CarbonImmutable::parse($data['from'])->startOfDay() : null,
                                filled($data['until'] ?? null) ? CarbonImmutable::parse($data['until'])->endOfDay() : null,
                            );

                            Notification::make()
                                ->title(__('resources/automations.run_title', ['name' => $record->name]))
                                ->body(__('resources/automations.run_body', [
                                    'generated' => $stats['generated'],
                                    'published' => $stats['published'],
                                    'queued' => $stats['queued'],
                                    'skipped' => $stats['skipped'],
                                ]))
                                ->success()
                                ->send();
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
