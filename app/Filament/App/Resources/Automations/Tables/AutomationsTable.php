<?php

namespace App\Filament\App\Resources\Automations\Tables;

use App\Filament\App\Resources\Automations\AutomationResource;
use App\Jobs\RunAutomationBackfill;
use App\Models\Automation;
use App\Models\Workspace;
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
                    ->label(__('resources/automations.col_name'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('enabled')
                    ->label(__('resources/automations.col_enabled'))
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
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->maxDate(now()),
                                DatePicker::make('until')
                                    ->label(__('resources/automations.run_until'))
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->maxDate(now()),
                            ]),
                        ])
                        ->visible(fn (Automation $record): bool => $record->enabled)
                        ->action(function (Automation $record, array $data): void {
                            $workspace = Workspace::findOrFail(session('current_workspace_id'));

                            // Queued: a backlog can mean hundreds of AI
                            // generations, far beyond the request timeout.
                            RunAutomationBackfill::dispatch(
                                (string) $workspace->id,
                                (int) $record->id,
                                filled($data['from'] ?? null) ? (string) $data['from'] : null,
                                filled($data['until'] ?? null) ? (string) $data['until'] : null,
                            );

                            Notification::make()
                                ->title(__('resources/automations.run_queued_title', ['name' => $record->name]))
                                ->body(__('resources/automations.run_queued_body'))
                                ->success()
                                ->send();
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
