<?php

declare(strict_types=1);

namespace App\Filament\App\Support;

use App\Models\AiAgent;
use App\Models\AutoReplyQueueItem;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\ReplyGenerator;
use App\Services\Billing\AiUsageService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Laravel\Ai\Enums\Lab;
use Throwable;

use function Laravel\Ai\agent;

/**
 * Shared building blocks for the "reply to a review" slide-over, used by both
 * the Reviews table and the Approvals (auto-reply queue) table: the review
 * preview, the AI-agent picker, the "Generate with AI" mini-button + inline
 * confirm, the emoji palette, and the AI generation itself.
 *
 * The reply textarea in both places is tagged data-emoji="reply" so the emoji
 * palette and the generate button target it identically. Translation keys live
 * under resources/reviews.* (generic labels shared by both screens).
 */
class ReplyComposer
{
    /** Read-only review preview block (author + rating, location + date, original + Google translation). */
    public static function reviewPreview(Review $review): HtmlString
    {
        $original = trim((string) ($review->originalText() ?? $review->text ?? ''));
        $translated = $review->translatedText();

        $meta = [];
        if ($review->location?->name) {
            $meta[] = e($review->location->name);
        }
        if ($review->created_at_external) {
            $meta[] = e($review->created_at_external->timezone(FilamentTimezone::get())->format('D, M j, Y · H:i'));
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

        $html .= '</div>';

        // Reviewer-uploaded photos (Google Business). Click a thumbnail to open
        // it full size in an in-place lightbox, so the reply slide-over stays put.
        $photos = array_values(array_filter((array) ($review->photos ?? []), fn ($u): bool => is_string($u) && $u !== ''));
        if ($photos !== []) {
            $html .= '<div x-data="{ open: false, src: \'\' }" @keydown.escape.window="open = false">';
            $html .= '<div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">';
            foreach (array_slice($photos, 0, 10) as $url) {
                $html .= '<img src="'.e($url).'" alt="" loading="lazy"'
                    .' @click="src = \''.e($url).'\'; open = true"'
                    .' style="width:64px; height:64px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; cursor:zoom-in;">';
            }
            $html .= '</div>';

            // Fullscreen overlay, teleported to <body> so it is fixed to the
            // viewport (the slide-over is transformed, which would otherwise
            // trap position:fixed inside it). The image is centered with an
            // absolute transform rather than flexbox on the overlay: Alpine's
            // x-show clears the overlay's inline `display`, which would drop a
            // `display:flex` and knock the image to the corner. Click closes.
            $html .= '<template x-teleport="body">'
                .'<div x-cloak x-show="open" @click="open = false"'
                .' style="position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:99999; background:rgba(0,0,0,.8); cursor:zoom-out;">'
                .'<img :src="src" alt="" style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); max-width:92vw; max-height:88vh; object-fit:contain; border-radius:8px; box-shadow:0 8px 40px rgba(0,0,0,.5);">'
                .'</div>'
                .'</template>';
            $html .= '</div>';
        }

        return new HtmlString($html);
    }

    /** The "author · ★★★★★" heading label for the preview placeholder. */
    public static function previewLabel(Review $review): string
    {
        return ($review->author_name ?: __('common.anonymous')).' · '.str_repeat('★', (int) $review->rating);
    }

    public static function agentSelect(): Select
    {
        return Select::make('ai_agent_id')
            ->label(__('resources/reviews.ai_agent'))
            ->options(fn (): array => AiAgent::query()->orderBy('name')->pluck('name', 'id')->all())
            ->placeholder(__('resources/reviews.default_agent'));
    }

    /** Human name of the current UI language (the translation target). */
    private static function uiLanguageName(): string
    {
        return app()->getLocale() === 'de' ? 'Deutsch' : 'English';
    }

    /**
     * "Show translation" under the reply field: replies are often drafted in
     * the reviewer's language (Arabic, Italian, …), so a click renders a plain
     * translation into the UI language below the textarea — mirroring the
     * "Translated by Google" block the review preview already shows.
     *
     * @return array<int, Component|Actions>
     */
    public static function translationComponents(string $sourceField): array
    {
        $language = self::uiLanguageName();

        return [
            Hidden::make('reply_translation')->dehydrated(false),

            Actions::make([
                Action::make('translateReply')
                    ->label(__('resources/reviews.show_translation', ['language' => $language]))
                    ->icon(Heroicon::OutlinedLanguage)
                    ->link()
                    ->action(function (Get $get, Set $set) use ($sourceField): void {
                        $text = trim((string) $get($sourceField));
                        if ($text === '') {
                            return;
                        }

                        if (($translated = self::translate($text)) !== null) {
                            $set('reply_translation', $translated);
                        }
                    }),
            ]),

            Placeholder::make('reply_translation_view')
                ->hiddenLabel()
                ->visible(fn (Get $get): bool => filled($get('reply_translation')))
                ->content(fn (Get $get): HtmlString => new HtmlString(
                    '<div style="padding:10px 12px; background:#f9fafb; border:1px solid #eef2f7; border-radius:8px; color:#6b7280; font-size:12px; white-space:pre-wrap;">'
                    .'<span style="display:block; text-transform:uppercase; letter-spacing:.04em; font-size:10px; color:#9ca3af; margin-bottom:2px;">'
                    .e(__('resources/reviews.translation_label', ['language' => self::uiLanguageName()]))
                    .'</span>'
                    .e((string) $get('reply_translation'))
                    .'</div>',
                )),
        ];
    }

    /**
     * Translate text into the UI language with a small, cheap model. Returns
     * null on failure (a notification explains). Logged in the AI usage log
     * with zero credit cost: translations are a courtesy, not billed usage.
     */
    public static function translate(string $text): ?string
    {
        $target = self::uiLanguageName();

        // Dev/fake driver: no external call, just a marked passthrough.
        if (config('services.ai.driver') === 'fake') {
            return '['.$target.'] '.$text;
        }

        try {
            $model = (string) config('services.ai.translate_model', 'claude-haiku-4-5-20251001');

            $response = agent(
                instructions: "You are a translator. Translate the user's message into {$target}. Output ONLY the translation: no quotes, no commentary. Preserve emojis, personal names and line breaks.",
            )->prompt($text, provider: Lab::Anthropic, model: $model);

            if ($workspace = Workspace::find(session('current_workspace_id'))) {
                app(AiCreditService::class)->logUsage(
                    $workspace,
                    'reply_translate',
                    $model,
                    (int) ($response->usage->promptTokens ?? 0),
                    (int) ($response->usage->completionTokens ?? 0),
                );
            }

            return trim((string) $response->text);
        } catch (Throwable $e) {
            Notification::make()
                ->title(__('resources/reviews.translation_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /** Cost line shown in the "Generate AI reply?" confirm modal. */
    public static function aiReplyCostHint(): string
    {
        $workspace = Workspace::find(session('current_workspace_id'));
        if ($workspace === null) {
            return __('resources/reviews.cost_generic');
        }

        $usage = app(AiUsageService::class);
        if (! $usage->canAutoReply($workspace)) {
            return __('resources/reviews.cost_all_used');
        }

        if ($usage->isServedFromCredits($workspace)) {
            return __('resources/reviews.cost_credit', ['count' => app(AiCreditService::class)->balance($workspace)]);
        }

        $remaining = $usage->remaining($workspace);

        return $remaining >= PHP_INT_MAX
            ? __('resources/reviews.cost_generic')
            : __('resources/reviews.cost_monthly', ['count' => $remaining]);
    }

    /**
     * "Generate with AI" mini-button with an INLINE confirm (no modal, so the
     * slide-over is never closed). Confirming clicks the hidden Filament
     * generate action (data-gen="reply") that runs the server-side generation.
     */
    public static function generateHintHtml(): string
    {
        // Emoji picker sits right next to the Generate button in the hint row.
        return '<span style="display:inline-flex;align-items:center;gap:6px;">'
            .self::emojiPickerHtml()
            .self::generateButtonHtml()
            .'</span>';
    }

    private static function generateButtonHtml(): string
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
     * Self-contained emoji palette (no external script) that inserts at the
     * cursor of the reply textarea (tagged data-emoji="reply") and notifies
     * Livewire via a native input event.
     */
    public static function emojiPickerHtml(): string
    {
        $emojis = ['😊', '😀', '😁', '😄', '🙂', '😉', '😍', '🤩', '🥳', '🙏', '👍', '👏', '🙌', '💪', '🔥', '⭐', '🌟', '✨', '❤️', '💙', '💚', '💜', '🎉', '🎊', '🥰', '😎', '🤗', '👋', '✅', '💯', '🚀', '🏆', '🎯', '😅', '😂', '🤝', '🧠', '🔑', '🗝️', '⏱️', '👌', '😇', '☺️', '🤘'];

        $buttons = '';
        foreach (array_unique($emojis) as $e) {
            $buttons .= '<button type="button" @click="insert(\''.$e.'\')" style="font-size:20px;line-height:1;background:none;border:0;cursor:pointer;padding:4px;border-radius:6px;" onmouseover="this.style.background=\'#f3f4f6\'" onmouseout="this.style.background=\'none\'">'.$e.'</button>';
        }

        $html = <<<'HTML'
            <style>[x-cloak]{display:none!important}</style>
            <div x-data="{ open:false, x:0, y:0, place(){ const r=this.$refs.btn.getBoundingClientRect(); const w=Math.min(340, window.innerWidth*0.9); this.x=Math.round(Math.max(8, Math.min(r.left, window.innerWidth - w - 8))); this.y=Math.round(r.bottom+6); }, toggle(){ if(!this.open){ this.place(); } this.open=!this.open; }, insert(em){ const ta=document.querySelector('[data-emoji=reply]'); if(ta){ const s=ta.selectionStart??ta.value.length, en=ta.selectionEnd??ta.value.length; ta.value=ta.value.slice(0,s)+em+ta.value.slice(en); ta.dispatchEvent(new Event('input',{bubbles:true})); ta.focus(); ta.selectionStart=ta.selectionEnd=s+em.length; } } }" @keydown.escape.window="open=false" style="display:inline-block;">
                <button type="button" x-ref="btn" @click.stop="toggle()" style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; color:#374151; cursor:pointer; font-weight:600; font-size:.72rem; line-height:1.3;">😊 <span x-text="open ? '%HIDE_EMOJI%' : '%ADD_EMOJI%'">%ADD_EMOJI%</span></button>
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
    public static function generateReply(Review $review, ?int $agentId): ?string
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

        $creditDelta = app(AiUsageService::class)->isServedFromCredits($workspace)
            ? -(int) config('services.ai.reply_credits', 1)
            : 0;
        app(AiCreditService::class)->logUsage(
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
}
