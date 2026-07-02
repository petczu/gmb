<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Reviews\Tables;

use App\Models\AiAgent;
use App\Models\AutoReplyQueueItem;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\ReplyGenerator;
use App\Services\Billing\AiUsageService;
use App\Services\Reviews\ReviewProviderFactory;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at_external', 'desc')
            ->persistSortInSession()
            // Classic table on desktop; secondary columns hidden on small screens
            // so it stays readable on mobile without horizontal scroll.
            ->columns([
                TextColumn::make('location.name')
                    ->label(__('resources/reviews.col_location'))
                    ->wrap()
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('rating')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('author_name')
                    ->label(__('resources/reviews.col_author'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('text')
                    ->label(__('resources/reviews.col_review'))
                    ->wrap()
                    ->limit(70)
                    ->state(fn (Review $record): ?string => $record->originalText())
                    ->tooltip(fn (Review $record): ?string => $record->translatedText()
                        ? $record->originalText()."\n\n(Google: ".$record->translatedText().')'
                        : $record->originalText())
                    ->searchable(),

                TextColumn::make('reply_text')
                    ->label(__('resources/reviews.col_reply'))
                    ->wrap()
                    ->limit(70)
                    ->placeholder(__('resources/reviews.no_reply'))
                    ->tooltip(fn (Review $record): ?string => $record->reply_text)
                    ->toggleable()
                    ->visibleFrom('lg'),

                TextColumn::make('reply_status')
                    ->label(__('resources/reviews.col_status'))
                    ->badge()
                    ->state(fn (Review $record): string => $record->reply_text ? __('resources/reviews.status_replied') : __('resources/reviews.status_pending'))
                    ->color(fn (Review $record): string => $record->reply_text ? 'success' : 'gray')
                    ->visibleFrom('sm'),

                TextColumn::make('created_at_external')
                    ->label(__('resources/reviews.col_date'))
                    ->dateTime('D, M j, Y · H:i')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Filter::make('date')
                    ->label(__('resources/reviews.review_date'))
                    ->schema([
                        DatePicker::make('from')->label(__('common.from'))->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                        DatePicker::make('until')->label(__('common.to'))->native(false)->maxDate(now())->prefixIcon('heroicon-o-calendar'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at_external', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d): Builder => $q->whereDate('created_at_external', '<=', $d)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('resources/reviews.filter_from', ['date' => Carbon::parse($data['from'])->translatedFormat('j. M Y')]);
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('resources/reviews.filter_to', ['date' => Carbon::parse($data['until'])->translatedFormat('j. M Y')]);
                        }

                        return $indicators;
                    }),

                SelectFilter::make('rating')
                    ->options([5 => '5★', 4 => '4★', 3 => '3★', 2 => '2★', 1 => '1★']),

                SelectFilter::make('location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('replied')
                    ->label(__('resources/reviews.reply_status'))
                    ->placeholder(__('common.all'))
                    ->trueLabel(__('resources/reviews.status_replied'))
                    ->falseLabel(__('resources/reviews.status_pending'))
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('reply_text'),
                        false: fn ($query) => $query->whereNull('reply_text'),
                    ),

                // Google reviews can be rating-only (no written text) — filter
                // those out, or surface them on their own. Distinct from reply
                // status (whether WE responded).
                TernaryFilter::make('has_text')
                    ->label(__('resources/reviews.review_text'))
                    ->placeholder(__('common.all'))
                    ->trueLabel(__('resources/reviews.with_text'))
                    ->falseLabel(__('resources/reviews.rating_only'))
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('text')->where('text', '!=', ''),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('text')->orWhere('text', '')),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                Action::make('reply')
                    ->label(fn (Review $record): string => $record->reply_text ? __('resources/reviews.edit_reply') : __('resources/reviews.reply'))
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('primary')
                    ->slideOver()
                    ->modalWidth(\Filament\Support\Enums\Width::Large)
                    ->modalHeading(fn (Review $record): string => $record->reply_text ? __('resources/reviews.edit_reply') : __('resources/reviews.reply_to_review'))
                    ->fillForm(fn (Review $record): array => ['reply_text' => $record->reply_text])
                    // Tag the Submit button so the client-side guard can find it.
                    ->modalSubmitAction(fn (Action $action) => $action->extraAttributes(['data-reply-submit' => '1']))
                    ->schema([
                        Placeholder::make('review_preview')
                            ->label(fn (Review $record): string => ($record->author_name ?: __('common.anonymous')).' · '.str_repeat('★', (int) $record->rating))
                            ->content(function (Review $record): \Illuminate\Support\HtmlString {
                                $original = trim((string) ($record->originalText() ?? $record->text ?? ''));
                                $translated = $record->translatedText();

                                // Location + date/time line.
                                $meta = [];
                                if ($record->location?->name) {
                                    $meta[] = e($record->location->name);
                                }
                                if ($record->created_at_external) {
                                    $meta[] = e($record->created_at_external
                                        ->timezone(\Filament\Support\Facades\FilamentTimezone::get())
                                        ->format('D, M j, Y · H:i'));
                                }
                                $html = $meta
                                    ? '<div style="font-size:11px; color:#6b7280; margin-bottom:6px;">'.implode(' · ', $meta).'</div>'
                                    : '';

                                $html .= '<div style="white-space:pre-wrap; padding:10px 12px; background:#f9fafb; border:1px solid #eef2f7; border-radius:8px; color:#374151;">';
                                if ($original === '') {
                                    $html .= '<span style="color:#9ca3af; font-style:italic;">'.e(__('resources/reviews.no_written_review')).'</span>';
                                } else {
                                    $html .= e($original);
                                    if ($translated && $translated !== $original) {
                                        $html .= '<div style="margin-top:8px; padding-top:8px; border-top:1px dashed #e5e7eb; color:#6b7280; font-size:12px;">'
                                            .'<span style="display:block; text-transform:uppercase; letter-spacing:.04em; font-size:10px; color:#9ca3af; margin-bottom:2px;">'.e(__('resources/reviews.translated_by_google')).'</span>'
                                            .e($translated).'</div>';
                                    }
                                }

                                return new \Illuminate\Support\HtmlString($html.'</div>');
                            }),

                        Select::make('ai_agent_id')
                            ->label(__('resources/reviews.ai_agent'))
                            ->options(fn (): array => AiAgent::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->placeholder(__('resources/reviews.default_agent')),

                        Textarea::make('reply_text')
                            ->label(__('resources/reviews.your_reply'))
                            ->required()
                            ->rows(5)
                            ->maxLength(4096)
                            // Visible mini-button + INLINE confirm (no second modal,
                            // so the slide-over is never closed). The hidden Filament
                            // action below does the actual server-side generation.
                            ->hint(fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(self::generateHintHtml()))
                            ->hintAction(
                                Action::make('generate')
                                    ->label(__('resources/reviews.generate_with_ai'))
                                    ->extraAttributes(['data-gen' => 'reply', 'class' => 'gen-hidden'])
                                    ->action(function (Set $set, Get $get, Review $record, \Livewire\Component $livewire): void {
                                        $text = self::generateReply($record, $get('ai_agent_id') ? (int) $get('ai_agent_id') : null);
                                        if ($text !== null) {
                                            $set('reply_text', $text);
                                        }
                                        // Tell the confirm popup the generation finished so it
                                        // can drop the spinner and close (success or no-op).
                                        $livewire->dispatch('reply-generated');
                                    }),
                            )
                            ->extraInputAttributes(['data-emoji' => 'reply']),

                        Placeholder::make('emoji_picker')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString(self::emojiPickerHtml())),

                        // Client-side guard: keep Submit disabled until the reply
                        // text differs from the original (handles typing AND AI fills).
                        Placeholder::make('submit_guard')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString(<<<'HTML'
                                <span x-data x-init="
                                    const ta = document.querySelector('[data-emoji=reply]');
                                    if (ta && ta.dataset.orig === undefined) { ta.dataset.orig = (ta.value || '').trim(); }
                                    const id = setInterval(() => {
                                        const ta = document.querySelector('[data-emoji=reply]');
                                        const btn = document.querySelector('[data-reply-submit]');
                                        if (!ta || !document.body.contains(ta)) { clearInterval(id); return; }
                                        if (!btn) return;
                                        const changed = (ta.value || '').trim() !== (ta.dataset.orig || '');
                                        btn.disabled = !changed;
                                        btn.style.opacity = changed ? '' : '0.55';
                                        btn.style.cursor = changed ? '' : 'not-allowed';
                                    }, 250);
                                "></span>
                                HTML)),

                        // Custom centered confirm overlays for Submit and Delete
                        // (native Filament modals would close the slide-over).
                        Placeholder::make('reply_confirms')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString(self::replyConfirmsHtml())),
                    ])
                    // Delete-reply button inside the slide-over (only when a reply
                    // exists). Closes the slide-over after deleting.
                    ->extraModalFooterActions([
                        Action::make('deleteReplyInline')
                            ->label(__('resources/reviews.delete_reply'))
                            ->icon(Heroicon::OutlinedTrash)
                            ->color('danger')
                            ->visible(fn (Review $record): bool => filled($record->reply_text))
                            // No native requiresConfirmation: a modal-over-slide-over
                            // closes the parent. A custom centered overlay (rendered in
                            // the reply_confirms placeholder) gates this click instead.
                            ->extraAttributes(['data-del-reply' => '1'])
                            ->cancelParentActions()
                            ->action(function (Review $record): void {
                                self::provider()->deleteReply(self::accountId($record), $record->external_review_id);

                                $record->forceFill([
                                    'reply_text' => null,
                                    'replied_at' => null,
                                    'reply_status' => null,
                                    'reply_source' => null,
                                ])->save();

                                Notification::make()->title(__('resources/reviews.reply_deleted'))->success()->send();
                            }),
                    ])
                    ->action(function (array $data, Review $record, \Filament\Actions\Action $action): void {
                        // Nothing to publish if the reply is unchanged.
                        if (trim((string) $data['reply_text']) === trim((string) ($record->reply_text ?? ''))) {
                            Notification::make()->title(__('resources/reviews.no_changes'))->warning()->send();
                            $action->halt();
                        }

                        self::provider()->reply(self::accountId($record), $record->external_review_id, $data['reply_text']);

                        $record->forceFill([
                            'reply_text' => $data['reply_text'],
                            'replied_at' => now(),
                            'reply_status' => 'published',
                            'reply_source' => 'manual',
                        ])->save();

                        Notification::make()->title(__('resources/reviews.reply_published'))->success()->send();
                    }),

                Action::make('deleteReply')
                    ->label(__('resources/reviews.delete_reply'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->visible(fn (Review $record): bool => filled($record->reply_text))
                    ->requiresConfirmation()
                    ->modalDescription(__('resources/reviews.delete_reply_desc'))
                    ->action(function (Review $record): void {
                        self::provider()->deleteReply(self::accountId($record), $record->external_review_id);

                        $record->forceFill([
                            'reply_text' => null,
                            'replied_at' => null,
                            'reply_status' => null,
                            'reply_source' => null,
                        ])->save();

                        Notification::make()->title(__('resources/reviews.reply_deleted'))->success()->send();
                    }),
                ]),
            ]);
    }

    /** Cost line shown in the "Generate AI reply?" confirm modal. */
    private static function aiReplyCostHint(): string
    {
        $workspace = Workspace::find(session('current_workspace_id'));
        if ($workspace === null) {
            return __('resources/reviews.cost_generic');
        }

        $usage = app(AiUsageService::class);
        if (! $usage->canAutoReply($workspace)) {
            return __('resources/reviews.cost_all_used');
        }

        // Plan allowance exhausted but purchased credits cover it.
        if ($usage->isServedFromCredits($workspace)) {
            $credits = app(\App\Services\Ai\AiCreditService::class)->balance($workspace);

            return __('resources/reviews.cost_credit', ['count' => $credits]);
        }

        $remaining = $usage->remaining($workspace);

        return $remaining >= PHP_INT_MAX
            ? __('resources/reviews.cost_generic')
            : __('resources/reviews.cost_monthly', ['count' => $remaining]);
    }

    /**
     * "Generate with AI" mini-button with an INLINE confirm (no modal, so the
     * reply slide-over is never closed). Confirming clicks the hidden Filament
     * generate action (data-gen="reply") that runs the server-side generation.
     */
    private static function generateHintHtml(): string
    {
        $cost = e(self::aiReplyCostHint());

        $html = <<<'HTML'
            <style>.gen-hidden{display:none!important}[x-cloak]{display:none!important}.gen-spin{width:1.75rem;height:1.75rem;border:3px solid #e5e7eb;border-top-color:#1800ff;border-radius:9999px;animation:gen-rot .7s linear infinite;margin:0 auto}@keyframes gen-rot{to{transform:rotate(360deg)}}</style>
            <span x-data="{ open:false, loading:false, run(){ this.loading=true; const t=document.querySelector('[data-gen=reply]'); if(t){ (t.querySelector('button,a')||t).click(); } setTimeout(()=>{ this.loading=false; this.open=false; }, 30000); }, done(){ this.loading=false; this.open=false; } }" @reply-generated.window="done()">
                <button type="button" @click="open=true" style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#1800ff;color:#fff;border:0;border-radius:8px;font-weight:600;font-size:.72rem;line-height:1.3;cursor:pointer !important;">✨ %GENERATE_WITH_AI%</button>

                <template x-teleport="body">
                    <div x-show="open" x-cloak @keydown.escape.window="if(!loading) open=false" x-transition.opacity
                        style="position:fixed;top:0;left:0;right:0;bottom:0;width:100vw;height:100vh;z-index:9999;">
                        <div @click="if(!loading) open=false" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(17,24,39,.5);"></div>
                        <div @click.stop
                            style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;max-width:28rem;width:calc(100vw - 2rem);padding:2rem 1.5rem 1.5rem;box-shadow:0 24px 48px -12px rgba(0,0,0,.35);text-align:center;">
                            <button type="button" x-show="!loading" @click="open=false" aria-label="Close" style="position:absolute;top:.9rem;right:.9rem;background:none;border:0;color:#9ca3af;cursor:pointer !important;padding:.25rem;line-height:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                            <div style="margin:0 auto .9rem;width:3.5rem;height:3.5rem;border-radius:9999px;background:#eef0ff;display:flex;align-items:center;justify-content:center;">
                                <div x-show="loading" class="gen-spin"></div>
                                <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1800ff"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
                            </div>
                            <div style="font-weight:700;font-size:1.25rem;color:#111827;margin-bottom:.45rem;">%GENERATE_WITH_AI%</div>
                            <div x-show="!loading" style="color:#6b7280;font-size:.95rem;line-height:1.5;margin-bottom:1.5rem;">%COST%</div>
                            <div x-show="loading" style="color:#6b7280;font-size:.95rem;line-height:1.5;margin-bottom:1.5rem;">%GENERATING%</div>
                            <div x-show="!loading" style="display:flex;gap:.75rem;">
                                <button type="button" @click="open=false" style="flex:1;padding:.7rem 1rem;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:10px;font-weight:600;font-size:.9rem;cursor:pointer !important;">%CANCEL%</button>
                                <button type="button" @click="run()" style="flex:1;padding:.7rem 1rem;background:#1800ff;color:#fff;border:0;border-radius:10px;font-weight:600;font-size:.9rem;cursor:pointer !important;">%GENERATE%</button>
                            </div>
                        </div>
                    </div>
                </template>
            </span>
            HTML;

        return str_replace(
            ['%COST%', '%GENERATE_WITH_AI%', '%GENERATE%', '%CANCEL%', '%GENERATING%'],
            [$cost, e(__('resources/reviews.generate_with_ai')), e(__('resources/reviews.generate')), e(__('resources/reviews.cancel')), e(__('resources/reviews.generating'))],
            $html,
        );
    }

    /**
     * Two centered confirm overlays (Submit, Delete) teleported to <body>, plus
     * an Alpine controller that intercepts the footer Submit / Delete buttons in
     * the capture phase: the first click is swallowed and opens the overlay; on
     * confirm we flag the button and re-click so it passes through. This keeps
     * both buttons in the slide-over footer while avoiding a modal-over-slide-over
     * (which Filament closes the parent for).
     */
    private static function replyConfirmsHtml(): string
    {
        $html = <<<'HTML'
            <style>[x-cloak]{display:none!important}</style>
            <span x-data="{ submitOpen:false, deleteOpen:false,
                init(){ const self=this;
                    setInterval(() => {
                        const sub=document.querySelector('[data-reply-submit]');
                        if(sub && !sub.dataset.cfHook){ sub.dataset.cfHook='1';
                            sub.addEventListener('click',(e)=>{ if(sub.dataset.cfOk){ return; } if(sub.disabled){ return; } e.preventDefault(); e.stopImmediatePropagation(); self.submitOpen=true; }, true); }
                        const del=document.querySelector('[data-del-reply]');
                        if(del && !del.dataset.cfHook){ del.dataset.cfHook='1';
                            del.addEventListener('click',(e)=>{ if(del.dataset.cfOk){ return; } e.preventDefault(); e.stopImmediatePropagation(); self.deleteOpen=true; }, true); }
                    }, 250);
                },
                fire(sel){ const b=document.querySelector(sel); if(b){ b.dataset.cfOk='1'; b.click(); setTimeout(()=>{ if(b){ delete b.dataset.cfOk; } }, 200); } },
                doSubmit(){ this.submitOpen=false; this.fire('[data-reply-submit]'); },
                doDelete(){ this.deleteOpen=false; this.fire('[data-del-reply]'); }
            }" x-init="init()">

                <template x-teleport="body">
                    <div x-show="submitOpen" x-cloak @keydown.escape.window="submitOpen=false" x-transition.opacity
                        style="position:fixed;top:0;left:0;right:0;bottom:0;width:100vw;height:100vh;z-index:9999;">
                        <div @click="submitOpen=false" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(17,24,39,.5);"></div>
                        <div @click.stop style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;max-width:28rem;width:calc(100vw - 2rem);padding:2rem 1.5rem 1.5rem;box-shadow:0 24px 48px -12px rgba(0,0,0,.35);text-align:center;">
                            <button type="button" @click="submitOpen=false" aria-label="Close" style="position:absolute;top:.9rem;right:.9rem;background:none;border:0;color:#9ca3af;cursor:pointer !important;padding:.25rem;line-height:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                            <div style="margin:0 auto .9rem;width:3.5rem;height:3.5rem;border-radius:9999px;background:#eef0ff;display:flex;align-items:center;justify-content:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1800ff"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                            </div>
                            <div style="font-weight:700;font-size:1.25rem;color:#111827;margin-bottom:.45rem;">%SUBMIT_HEADING%</div>
                            <div style="color:#6b7280;font-size:.95rem;line-height:1.5;margin-bottom:1.5rem;">%SUBMIT_DESC%</div>
                            <div style="display:flex;gap:.75rem;">
                                <button type="button" @click="submitOpen=false" style="flex:1;padding:.7rem 1rem;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:10px;font-weight:600;font-size:.9rem;cursor:pointer !important;">%CANCEL%</button>
                                <button type="button" @click="doSubmit()" style="flex:1;padding:.7rem 1rem;background:#1800ff;color:#fff;border:0;border-radius:10px;font-weight:600;font-size:.9rem;cursor:pointer !important;">%SUBMIT_CONFIRM%</button>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-teleport="body">
                    <div x-show="deleteOpen" x-cloak @keydown.escape.window="deleteOpen=false" x-transition.opacity
                        style="position:fixed;top:0;left:0;right:0;bottom:0;width:100vw;height:100vh;z-index:9999;">
                        <div @click="deleteOpen=false" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(17,24,39,.5);"></div>
                        <div @click.stop style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;max-width:28rem;width:calc(100vw - 2rem);padding:2rem 1.5rem 1.5rem;box-shadow:0 24px 48px -12px rgba(0,0,0,.35);text-align:center;">
                            <button type="button" @click="deleteOpen=false" aria-label="Close" style="position:absolute;top:.9rem;right:.9rem;background:none;border:0;color:#9ca3af;cursor:pointer !important;padding:.25rem;line-height:0;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                            <div style="margin:0 auto .9rem;width:3.5rem;height:3.5rem;border-radius:9999px;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#dc2626"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </div>
                            <div style="font-weight:700;font-size:1.25rem;color:#111827;margin-bottom:.45rem;">%DELETE_HEADING%</div>
                            <div style="color:#6b7280;font-size:.95rem;line-height:1.5;margin-bottom:1.5rem;">%DELETE_DESC%</div>
                            <div style="display:flex;gap:.75rem;">
                                <button type="button" @click="deleteOpen=false" style="flex:1;padding:.7rem 1rem;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:10px;font-weight:600;font-size:.9rem;cursor:pointer !important;">%CANCEL%</button>
                                <button type="button" @click="doDelete()" style="flex:1;padding:.7rem 1rem;background:#dc2626;color:#fff;border:0;border-radius:10px;font-weight:600;font-size:.9rem;cursor:pointer !important;">%DELETE_CONFIRM%</button>
                            </div>
                        </div>
                    </div>
                </template>
            </span>
            HTML;

        return str_replace(
            ['%SUBMIT_HEADING%', '%SUBMIT_DESC%', '%SUBMIT_CONFIRM%', '%DELETE_HEADING%', '%DELETE_DESC%', '%DELETE_CONFIRM%', '%CANCEL%'],
            [
                e(__('resources/reviews.submit_heading')),
                e(__('resources/reviews.submit_desc')),
                e(__('resources/reviews.submit_confirm')),
                e(__('resources/reviews.delete_reply')),
                e(__('resources/reviews.delete_reply_desc')),
                e(__('resources/reviews.delete_confirm')),
                e(__('resources/reviews.cancel')),
            ],
            $html,
        );
    }

    /**
     * Self-contained emoji palette (no external script) that inserts at the
     * cursor of the reply textarea (tagged data-emoji="reply") and notifies
     * Livewire via a native input event.
     */
    private static function emojiPickerHtml(): string
    {
        $emojis = ['😊', '😀', '😁', '😄', '🙂', '😉', '😍', '🤩', '🥳', '🙏', '👍', '👏', '🙌', '💪', '🔥', '⭐', '🌟', '✨', '❤️', '💙', '💚', '💜', '🎉', '🎊', '🥰', '😎', '🤗', '👋', '✅', '💯', '🚀', '🏆', '🎯', '😅', '😂', '🤝', '🧠', '🔑', '🗝️', '⏱️', '👌', '😇', '☺️', '🤘'];

        $buttons = '';
        foreach (array_unique($emojis) as $e) {
            $buttons .= '<button type="button" @click="insert(\''.$e.'\')" style="font-size:20px;line-height:1;background:none;border:0;cursor:pointer;padding:4px;border-radius:6px;" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'none\'">'.$e.'</button>';
        }

        // The palette is TELEPORTED to <body> and positioned fixed at the button,
        // so the modal's overflow can't clip it and it floats on top instead of
        // stretching the modal. Nowdoc keeps JS "$"/"${}" literal.
        $html = <<<'HTML'
            <style>[x-cloak]{display:none!important}</style>
            <div x-data="{ open:false, x:0, y:0, place(){ const r=this.$refs.btn.getBoundingClientRect(); this.x=Math.round(r.left); this.y=Math.round(r.bottom+6); }, toggle(){ if(!this.open){ this.place(); } this.open=!this.open; }, insert(em){ const ta=document.querySelector('[data-emoji=reply]'); if(ta){ const s=ta.selectionStart??ta.value.length, en=ta.selectionEnd??ta.value.length; ta.value=ta.value.slice(0,s)+em+ta.value.slice(en); ta.dispatchEvent(new Event('input',{bubbles:true})); ta.focus(); ta.selectionStart=ta.selectionEnd=s+em.length; } } }" @keydown.escape.window="open=false" style="display:inline-block;">
                <button type="button" x-ref="btn" @click.stop="toggle()" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; cursor:pointer; font-size:13px;">😊 <span x-text="open ? '%HIDE_EMOJI%' : '%ADD_EMOJI%'">%ADD_EMOJI%</span></button>
                <template x-teleport="body">
                    <div x-show="open" x-cloak @click.outside="open=false" :style="`position:fixed; top:${y}px; left:${x}px; z-index:9999; width:340px; max-width:90vw; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 12px 32px rgba(0,0,0,.18); padding:8px; display:flex; flex-wrap:wrap; gap:2px;`">%BUTTONS%</div>
                </template>
            </div>
            HTML;

        return str_replace(
            ['%BUTTONS%', '%ADD_EMOJI%', '%HIDE_EMOJI%'],
            [$buttons, e(__('resources/reviews.add_emoji')), e(__('resources/reviews.hide_emoji'))],
            $html,
        );
    }

    /**
     * Generate an AI reply for the review using the chosen (or default) agent.
     * Counts as one AI reply against the plan's monthly allowance. Returns the
     * text, or null if blocked/failed (a notification explains why).
     */
    private static function generateReply(Review $review, ?int $agentId): ?string
    {
        $workspace = once(fn () => Workspace::find(session('current_workspace_id')));
        if ($workspace === null) {
            return null;
        }

        if (! app(AiUsageService::class)->canAutoReply($workspace)) {
            app(AiUsageService::class)->notifyLimitReachedOnce($workspace);

            Notification::make()->title(__('resources/reviews.ai_limit_reached'))
                ->body(__('resources/reviews.ai_limit_body'))
                ->warning()->send();

            return null;
        }

        $agent = $agentId ? AiAgent::find($agentId) : (AiAgent::where('is_default', true)->first() ?? AiAgent::first());

        try {
            $generated = app(ReplyGenerator::class)->generate(
                reviewText: (string) ($review->originalText() ?? $review->text),
                rating: (int) $review->rating,
                authorName: $review->author_name,
                businessName: (string) ($review->location?->name ?? 'our business'),
                tone: $agent?->tone,
                instruction: $agent?->instructions(),
                // Default to the review's own language (null). Only force English
                // when an agent explicitly opts out of native-language replies.
                language: ($agent && ! $agent->reply_native_language) ? 'English' : null,
            );
        } catch (Throwable $e) {
            Notification::make()->title(__('resources/reviews.generation_failed'))->body($e->getMessage())->danger()->send();

            return null;
        }

        // Record the generation so it counts toward the monthly AI allowance
        // (status 'draft' keeps it out of the Approvals queue).
        AutoReplyQueueItem::create([
            'review_id' => $review->id,
            'generated_text' => $generated->text,
            'status' => 'draft',
            'mode' => 'manual',
            'model' => $generated->model,
            'credits_spent' => 1,
        ]);

        // Log the AI call (model, tokens, real USD cost) on the usage ledger.
        // Plan-included usage is delta 0; once the plan allowance is exhausted
        // the reply is served from purchased credits, so the same row also
        // debits the balance (decided before logging).
        $creditDelta = app(AiUsageService::class)->isServedFromCredits($workspace)
            ? -(int) config('services.ai.reply_credits', 1)
            : 0;
        app(\App\Services\Ai\AiCreditService::class)->logUsage(
            $workspace,
            'manual_reply',
            $generated->model,
            $generated->inputTokens,
            $generated->outputTokens,
            $creditDelta,
            'review',
            (string) $review->id,
        );

        Notification::make()->title(__('resources/reviews.reply_generated'))->success()->send();

        return $generated->text;
    }

    private static function provider(): \App\Services\Reviews\ReviewProvider
    {
        return app(ReviewProviderFactory::class)->make();
    }

    private static function accountId(Review $record): string
    {
        return $record->location?->zernio_account_id ?? 'fake-account';
    }
}
