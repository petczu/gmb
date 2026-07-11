<?php

namespace App\Filament\App\Resources\AiAgents\Schemas;

use App\Models\AiAgent;
use App\Models\Review;
use App\Models\Workspace;
use App\Services\Ai\AgentDescriptionGenerator;
use App\Services\Ai\AiCreditService;
use App\Services\Ai\ReplyGenerator;
use App\Support\AiRateLimit;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAgentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Two side-by-side columns (stacked on small screens): agent | test.
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                self::agentSection()->columnSpan(1),
                self::testSection()->columnSpan(1),
            ]);
    }

    /** Left column: the agent's persona, knowledge base, tone and toggles. */
    private static function agentSection(): Section
    {
        return Section::make(__('resources/ai_agents.section'))
            ->description(__('resources/ai_agents.section_desc'))
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(120),

                Textarea::make('description')
                    ->label(__('resources/ai_agents.describe'))
                    ->helperText(__('resources/ai_agents.describe_helper'))
                    ->rows(12)
                    ->required()
                    // Let Claude draft the persona from a website URL and/or a few notes.
                    ->hintAction(
                        Action::make('generateDescription')
                            ->label(__('resources/ai_agents.generate_label'))
                            ->icon(Heroicon::OutlinedSparkles)
                            ->modalHeading(__('resources/ai_agents.generate_heading'))
                            ->modalDescription(__('resources/ai_agents.generate_desc'))
                            ->modalSubmitActionLabel(__('resources/ai_agents.generate_submit'))
                            ->schema([
                                TextInput::make('website_url')
                                    ->label(__('resources/ai_agents.generate_url'))
                                    ->url()
                                    ->placeholder('https://example.com'),
                                Textarea::make('notes')
                                    ->label(__('resources/ai_agents.generate_notes'))
                                    ->placeholder(__('resources/ai_agents.generate_notes_ph'))
                                    ->rows(3),
                            ])
                            ->action(function (array $data, Set $set): void {
                                $url = trim((string) ($data['website_url'] ?? ''));
                                $notes = trim((string) ($data['notes'] ?? ''));

                                if ($url === '' && $notes === '') {
                                    Notification::make()->title(__('resources/ai_agents.generate_need_input'))->warning()->send();

                                    return;
                                }

                                if (AiRateLimit::hit('agent-desc-gen')) {
                                    Notification::make()->title(__('resources/ai_agents.generate_rate_limited'))->warning()->send();

                                    return;
                                }

                                try {
                                    $text = app(AgentDescriptionGenerator::class)->generate(
                                        $url !== '' ? $url : null,
                                        $notes !== '' ? $notes : null,
                                        Workspace::find((string) session('current_workspace_id')),
                                    );
                                    $set('description', $text);
                                    Notification::make()->title(__('resources/ai_agents.generate_done'))->success()->send();
                                } catch (\Throwable $e) {
                                    Log::warning('Agent description generation failed', ['error' => $e->getMessage()]);
                                    Notification::make()->title(__('resources/ai_agents.generate_failed'))->danger()->send();
                                }
                            }),
                    ),

                Textarea::make('knowledge')
                    ->label(__('resources/ai_agents.knowledge'))
                    ->helperText(__('resources/ai_agents.knowledge_helper'))
                    ->placeholder(__('resources/ai_agents.knowledge_ph'))
                    ->rows(6),

                Select::make('tone')
                    ->label(__('resources/ai_agents.tone'))
                    ->options(AiAgent::tones())
                    ->default('professional')
                    ->native(false)
                    ->required(),

                Toggle::make('reply_native_language')
                    ->label(__('resources/ai_agents.reply_native'))
                    ->helperText(__('resources/ai_agents.reply_native_helper'))
                    ->default(true),

                Toggle::make('is_default')
                    ->label(__('resources/ai_agents.default_agent'))
                    ->helperText(__('resources/ai_agents.default_agent_helper')),
            ]);
    }

    /** Right column: try the agent on a real synced review using live form values. */
    private static function testSection(): Section
    {
        return Section::make(__('resources/ai_agents.test_section'))
            ->description(__('resources/ai_agents.test_section_desc'))
            ->schema([
                Select::make('_test_review_id')
                    ->label(__('resources/ai_agents.test_pick_review'))
                    ->placeholder(__('resources/ai_agents.test_pick_placeholder'))
                    ->dehydrated(false)
                    ->live()
                    ->searchable()
                    ->options(fn (): array => Review::query()
                        ->latest('created_at_external')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Review $r): array => [
                            $r->id => Str::limit((string) ($r->author_name ?: 'Anonymous'), 22)
                                .' · '.str_repeat('★', (int) $r->rating)
                                .' · '.Str::limit((string) ($r->originalText() ?? $r->text), 40),
                        ])
                        ->all()),

                Placeholder::make('_test_review_preview')
                    ->label(__('resources/ai_agents.test_review_text'))
                    ->visible(fn (Get $get): bool => filled($get('_test_review_id')))
                    ->content(function (Get $get): string {
                        $review = Review::find($get('_test_review_id'));

                        return $review ? (string) ($review->originalText() ?? $review->text) : '';
                    }),

                Actions::make([
                    Action::make('generateTest')
                        ->label(__('resources/ai_agents.test_generate'))
                        ->icon(Heroicon::OutlinedSparkles)
                        ->action(fn (Get $get, Set $set) => self::runTest($get, $set)),
                ]),

                Textarea::make('_test_result')
                    ->label(__('resources/ai_agents.test_result'))
                    ->dehydrated(false)
                    ->readOnly()
                    ->rows(10)
                    ->visible(fn (Get $get): bool => filled($get('_test_result'))),
            ]);
    }

    /** Generate a draft reply from the LIVE (unsaved) form values — a free helper. */
    private static function runTest(Get $get, Set $set): void
    {
        $reviewId = $get('_test_review_id');
        if (! $reviewId) {
            Notification::make()->title(__('resources/ai_agents.test_need_review'))->warning()->send();

            return;
        }

        if (AiRateLimit::hit('agent-test')) {
            Notification::make()->title(__('resources/ai_agents.generate_rate_limited'))->warning()->send();

            return;
        }

        $review = Review::find($reviewId);
        if ($review === null) {
            return;
        }

        try {
            $generated = app(ReplyGenerator::class)->generate(
                reviewText: (string) ($review->originalText() ?? $review->text),
                rating: (int) $review->rating,
                authorName: $review->author_name,
                businessName: (string) ($review->location?->name ?? 'our business'),
                tone: (string) $get('tone'),
                instruction: AiAgent::buildInstructions((string) $get('description'), $get('knowledge')),
                language: $get('reply_native_language') ? null : 'English',
            );

            $set('_test_result', $generated->text);

            // Free helper: log the cost for auditing, but never debit the cap/credits.
            if ($workspace = Workspace::find((string) session('current_workspace_id'))) {
                app(AiCreditService::class)->logUsage(
                    $workspace,
                    'agent_test',
                    $generated->model,
                    $generated->inputTokens,
                    $generated->outputTokens,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Agent test generation failed', ['error' => $e->getMessage()]);
            Notification::make()->title(__('resources/ai_agents.generate_failed'))->danger()->send();
        }
    }
}
