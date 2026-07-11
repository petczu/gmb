<?php

namespace App\Filament\App\Resources\AutoReplyQueueItems\Tables;

use App\Filament\App\Support\ReplyComposer;
use App\Models\AutoReplyQueueItem;
use App\Models\Location;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AutoReplyService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;

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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotIn('status', ['draft', 'scheduled'])->with('review.location'))
            ->columns([
                TextColumn::make('review.location.name')
                    ->label(__('resources/auto_reply.col_location'))
                    ->wrap()
                    ->toggleable()
                    ->visibleFrom('lg'),

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
                    // Failed drafts carry the publish error; surface it on hover.
                    ->tooltip(fn (AutoReplyQueueItem $record): ?string => $record->status === 'failed' ? $record->error : null)
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

                // Review date window (filters through the related review).
                Filter::make('date')
                    ->label(__('resources/auto_reply.filter_date'))
                    ->schema([
                        DatePicker::make('from')->label(__('common.from'))->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                        DatePicker::make('until')->label(__('common.to'))->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d): Builder => $q->whereHas('review', fn (Builder $r) => $r->whereDate('created_at_external', '>=', $d)))
                        ->when($data['until'] ?? null, fn (Builder $q, $d): Builder => $q->whereHas('review', fn (Builder $r) => $r->whereDate('created_at_external', '<=', $d))))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('resources/auto_reply.filter_from', ['date' => Carbon::parse($data['from'])->translatedFormat('j. M Y')]);
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('resources/auto_reply.filter_to', ['date' => Carbon::parse($data['until'])->translatedFormat('j. M Y')]);
                        }

                        return $indicators;
                    }),

                SelectFilter::make('location')
                    ->label(__('resources/auto_reply.col_location'))
                    ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $id): Builder => $q->whereHas('review', fn (Builder $r) => $r->where('location_id', $id)))),

                SelectFilter::make('rating')
                    ->label(__('resources/auto_reply.col_rating'))
                    ->options([5 => '5★', 4 => '4★', 3 => '3★', 2 => '2★', 1 => '1★'])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $q, $rating): Builder => $q->whereHas('review', fn (Builder $r) => $r->where('rating', $rating)))),

                SelectFilter::make('source')
                    ->label(__('resources/auto_reply.col_source'))
                    ->options([
                        'ai' => __('resources/auto_reply.source_ai'),
                        'template' => __('resources/auto_reply.source_template'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(($data['value'] ?? null) === 'ai', fn (Builder $q): Builder => $q->whereNotNull('model'))
                        ->when(($data['value'] ?? null) === 'template', fn (Builder $q): Builder => $q->whereNull('model'))),
            ])
            // Only pending drafts get a checkbox: published/skipped rows have
            // nothing left to decide, so bulk actions can't touch them.
            ->checkIfRecordIsSelectableUsing(fn (AutoReplyQueueItem $record): bool => $record->status === 'pending')
            ->toolbarActions([
                BulkAction::make('approveSelected')
                    ->label(__('resources/auto_reply.approve_selected'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(__('resources/auto_reply.bulk_approve_confirm'))
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $queued = 0;

                        foreach ($records as $record) {
                            if ($record->status !== 'pending') {
                                continue;
                            }

                            // Queue instead of publishing inline: a big selection
                            // would run into the request timeout and Zernio rate
                            // limits. The post-due scheduler (auto-reply:post-due,
                            // every 5 minutes) publishes these sequentially and
                            // parks failures as `failed` with the reason. A small
                            // stagger keeps large batches gentle on the API.
                            $record->forceFill([
                                'status' => 'scheduled',
                                'post_at' => now()->addSeconds($queued * 15),
                                'decided_by' => Auth::id(),
                                'decided_at' => now(),
                            ])->save();

                            $queued++;
                        }

                        Notification::make()
                            ->title(__('resources/auto_reply.bulk_queued', ['count' => $queued]))
                            ->body(__('resources/auto_reply.bulk_queued_body'))
                            ->success()
                            ->send();
                    }),

                BulkAction::make('rejectSelected')
                    ->label(__('resources/auto_reply.reject_selected'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('resources/auto_reply.bulk_reject_confirm'))
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $service = app(AutoReplyService::class);
                        $count = 0;

                        foreach ($records as $record) {
                            if ($record->status !== 'pending') {
                                continue;
                            }
                            $service->reject($record, Auth::id());
                            $count++;
                        }

                        Notification::make()
                            ->title(__('resources/auto_reply.bulk_rejected', ['count' => $count]))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('review')
                    ->label(__('resources/auto_reply.review_reply'))
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('primary')
                    ->iconButton()
                    ->tooltip(__('resources/auto_reply.review_reply'))
                    ->slideOver()
                    ->modalWidth(Width::Large)
                    ->modalHeading(__('resources/auto_reply.review_reply'))
                    ->visible(fn (AutoReplyQueueItem $record): bool => $record->status === 'pending' && $record->review !== null)
                    ->fillForm(fn (AutoReplyQueueItem $record): array => [
                        'generated_text' => $record->generated_text,
                        // Preselect the agent that actually generated this draft.
                        'ai_agent_id' => $record->ai_agent_id,
                    ])
                    ->schema([
                        Placeholder::make('review_preview')
                            ->label(fn (AutoReplyQueueItem $record): string => $record->review ? ReplyComposer::previewLabel($record->review) : '')
                            ->content(fn (AutoReplyQueueItem $record): HtmlString => $record->review ? ReplyComposer::reviewPreview($record->review) : new HtmlString('')),

                        ReplyComposer::agentSelect(),

                        Textarea::make('generated_text')
                            ->label(__('resources/reviews.your_reply'))
                            ->required()
                            ->rows(5)
                            ->maxLength(4096)
                            ->hint(fn (): HtmlString => new HtmlString(ReplyComposer::generateHintHtml()))
                            ->hintAction(
                                Action::make('generate')
                                    ->label(__('resources/reviews.generate_with_ai'))
                                    ->extraAttributes(['data-gen' => 'reply', 'class' => 'gen-hidden'])
                                    ->action(function (Set $set, Get $get, AutoReplyQueueItem $record, Component $livewire): void {
                                        if ($record->review === null) {
                                            return;
                                        }
                                        $text = ReplyComposer::generateReply($record->review, $get('ai_agent_id') ? (int) $get('ai_agent_id') : null);
                                        if ($text !== null) {
                                            $set('generated_text', $text);
                                            // The old translation no longer matches.
                                            $set('reply_translation', null);
                                        }
                                        $livewire->dispatch('reply-generated');
                                    }),
                            )
                            ->extraInputAttributes(['data-emoji' => 'reply']),

                        ...ReplyComposer::translationComponents('generated_text'),

                        ReplyComposer::emojiPickerPlaceholder(),
                    ])
                    ->modalSubmitActionLabel(__('resources/auto_reply.approve_publish'))
                    ->extraModalFooterActions([
                        Action::make('rejectInline')
                            ->label(__('resources/auto_reply.reject'))
                            ->icon(Heroicon::OutlinedXMark)
                            ->color('danger')
                            ->cancelParentActions()
                            ->requiresConfirmation()
                            ->action(function (AutoReplyQueueItem $record): void {
                                app(AutoReplyService::class)->reject($record, Auth::id());
                                Notification::make()->title(__('resources/auto_reply.draft_rejected'))->success()->send();
                            }),
                    ])
                    ->action(function (array $data, AutoReplyQueueItem $record): void {
                        $record->update(['generated_text' => $data['generated_text']]);
                        $workspace = Workspace::find(session('current_workspace_id'));

                        try {
                            app(AutoReplyService::class)->approve($workspace, $record->fresh(), Auth::id());
                        } catch (\Throwable $e) {
                            self::notifyPublishFailure($e);

                            return;
                        }

                        Notification::make()->title(__('resources/auto_reply.reply_published'))->success()->send();
                    }),

                Action::make('approve')
                    ->label(__('resources/auto_reply.approve'))
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->iconButton()
                    ->tooltip(__('resources/auto_reply.approve'))
                    ->visible(fn (AutoReplyQueueItem $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (AutoReplyQueueItem $record): void {
                        $workspace = Workspace::find(session('current_workspace_id'));

                        try {
                            app(AutoReplyService::class)->approve($workspace, $record, Auth::id());
                        } catch (\Throwable $e) {
                            self::notifyPublishFailure($e);

                            return;
                        }

                        Notification::make()->title(__('resources/auto_reply.reply_published'))->success()->send();
                    }),

                Action::make('reject')
                    ->label(__('resources/auto_reply.reject'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->iconButton()
                    ->tooltip(__('resources/auto_reply.reject'))
                    ->visible(fn (AutoReplyQueueItem $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (AutoReplyQueueItem $record): void {
                        app(AutoReplyService::class)->reject($record, Auth::id());
                        Notification::make()->title(__('resources/auto_reply.draft_rejected'))->success()->send();
                    }),
            ]);
    }

    /**
     * Explain a failed publish in human terms. A 404 from Zernio means the
     * review is gone on Google's side (deleted by the author, or the location
     * was reconnected under a new account), which deserves a clearer message
     * than the raw API error.
     */
    private static function notifyPublishFailure(\Throwable $e): void
    {
        $message = $e->getMessage();
        $notFound = str_contains($message, '404') || str_contains(strtolower($message), 'not found');

        Notification::make()
            ->title(__('resources/auto_reply.publish_failed_title'))
            ->body($notFound
                ? __('resources/auto_reply.publish_not_found')
                : __('resources/auto_reply.publish_error', ['message' => mb_substr($message, 0, 200)]))
            ->danger()
            ->persistent()
            ->send();
    }
}
