<?php

namespace App\Filament\App\Resources\AutoReplyQueueItems\Tables;

use App\Models\AutoReplyQueueItem;
use App\Models\Workspace;
use App\Services\Ai\AutoReplyService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AutoReplyQueueItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->searchable(AutoReplyQueueItem::query()->exists())
            ->emptyStateIcon(Heroicon::OutlinedInboxStack)
            ->emptyStateHeading(__('resources/auto_reply.empty_heading'))
            ->emptyStateDescription(__('resources/auto_reply.empty_desc'))
            // Manual generations from the reply modal are usage records, not
            // approvals; `scheduled` auto-replies are awaiting their organic post
            // time, not a human decision. Keep both out of the approvals queue.
            ->modifyQueryUsing(fn ($query) => $query->whereNotIn('status', ['draft', 'scheduled']))
            ->columns([
                TextColumn::make('review.author_name')->label(__('resources/auto_reply.col_author')),

                TextColumn::make('review.rating')
                    ->label(__('resources/auto_reply.col_rating'))
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state).str_repeat('☆', 5 - $state) : '—')
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('review.text')
                    ->label(__('resources/auto_reply.col_review'))
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn (AutoReplyQueueItem $record): ?string => $record->review?->text)
                    ->visibleFrom('lg'),

                TextColumn::make('generated_text')
                    ->label(__('resources/auto_reply.col_ai_reply'))
                    ->limit(70)
                    ->wrap()
                    ->tooltip(fn (AutoReplyQueueItem $record): string => $record->generated_text)
                    ->visibleFrom('md'),

                TextColumn::make('status')
                    ->label(__('resources/auto_reply.col_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('resources/auto_reply.status_'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('model')
                    ->label(__('resources/auto_reply.col_source'))
                    ->formatStateUsing(fn (?string $state): string => $state ? __('resources/auto_reply.source_ai') : __('resources/auto_reply.source_template'))
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'info' : 'gray')
                    ->visibleFrom('lg'),

                TextColumn::make('created_at')->label(__('resources/auto_reply.col_generated'))->since()->sortable()->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => __('resources/auto_reply.status_pending'),
                        'published' => __('resources/auto_reply.status_published'),
                        'skipped' => __('resources/auto_reply.status_skipped'),
                        'failed' => __('resources/auto_reply.status_failed'),
                    ])
                    ->default('pending')
                    // Colour the active-filter chip to match the status badge
                    // (green default) instead of the brand-primary blue.
                    ->indicateUsing(function (array $state): ?Indicator {
                        $value = $state['value'] ?? null;
                        if (blank($value)) {
                            return null;
                        }

                        $labels = [
                            'pending' => __('resources/auto_reply.status_pending'),
                            'published' => __('resources/auto_reply.status_published'),
                            'skipped' => __('resources/auto_reply.status_skipped'),
                            'failed' => __('resources/auto_reply.status_failed'),
                        ];
                        $colors = ['pending' => 'warning', 'published' => 'success', 'skipped' => 'gray', 'failed' => 'danger'];

                        return Indicator::make(__('resources/auto_reply.status_indicator', ['status' => $labels[$value] ?? $value]))
                            ->color($colors[$value] ?? 'success');
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                Action::make('approve')
                    ->label(__('resources/auto_reply.approve'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (AutoReplyQueueItem $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (AutoReplyQueueItem $record): void {
                        $workspace = Workspace::find(session('current_workspace_id'));
                        app(AutoReplyService::class)->approve($workspace, $record, Auth::id());
                        Notification::make()->title(__('resources/auto_reply.reply_published'))->success()->send();
                    }),

                Action::make('edit')
                    ->label(__('resources/auto_reply.edit_publish'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (AutoReplyQueueItem $record): bool => $record->status === 'pending')
                    ->fillForm(fn (AutoReplyQueueItem $record): array => ['generated_text' => $record->generated_text])
                    ->schema([
                        Textarea::make('generated_text')->label(__('resources/auto_reply.reply'))->required()->rows(4)->maxLength(4096),
                    ])
                    ->action(function (array $data, AutoReplyQueueItem $record): void {
                        $record->update(['generated_text' => $data['generated_text']]);
                        $workspace = Workspace::find(session('current_workspace_id'));
                        app(AutoReplyService::class)->approve($workspace, $record->fresh(), Auth::id());
                        Notification::make()->title(__('resources/auto_reply.reply_published'))->success()->send();
                    }),

                Action::make('reject')
                    ->label(__('resources/auto_reply.reject'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (AutoReplyQueueItem $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (AutoReplyQueueItem $record): void {
                        app(AutoReplyService::class)->reject($record, Auth::id());
                        Notification::make()->title(__('resources/auto_reply.draft_rejected'))->success()->send();
                    }),
                ]),
            ]);
    }
}
