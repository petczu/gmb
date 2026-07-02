<?php

namespace App\Filament\App\Resources\AiAgents\Tables;

use App\Filament\App\Resources\AiAgents\AiAgentResource;
use App\Models\AiAgent;
use App\Models\Review;
use App\Services\Ai\ReplyGenerator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiAgentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchable(AiAgent::query()->exists())
            ->emptyStateIcon(Heroicon::OutlinedSparkles)
            ->emptyStateHeading(__('resources/ai_agents.empty_heading'))
            ->emptyStateDescription(__('resources/ai_agents.empty_desc'))
            ->emptyStateActions([
                Action::make('create')
                    ->label(__('resources/ai_agents.empty_cta'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->url(fn (): string => AiAgentResource::getUrl('create')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->description(fn (AiAgent $record): string => str($record->description)->limit(80))
                    ->searchable(),

                TextColumn::make('tone')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AiAgent::tones()[$state] ?? ucfirst($state)),

                IconColumn::make('reply_native_language')
                    ->label(__('resources/ai_agents.col_native_lang'))
                    ->boolean()
                    ->visibleFrom('md'),

                IconColumn::make('is_default')
                    ->label(__('resources/ai_agents.col_default'))
                    ->boolean()
                    ->visibleFrom('md'),

                TextColumn::make('updated_at')
                    ->label(__('resources/ai_agents.col_updated'))
                    ->since()
                    ->sortable()
                    ->visibleFrom('lg'),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::testAction(),
                    EditAction::make(),
                    Action::make('makeDefault')
                        ->label(__('resources/ai_agents.set_default'))
                        ->icon(Heroicon::OutlinedStar)
                        ->visible(fn (AiAgent $record): bool => ! $record->is_default)
                        ->action(fn (AiAgent $record) => $record->makeDefault()),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Test & preview: generate a sample reply on the latest review using this
     * agent's persona/tone (via the configured generator, fake or Claude).
     */
    public static function testAction(): Action
    {
        return Action::make('test')
            ->label(__('resources/ai_agents.test_preview'))
            ->icon(Heroicon::OutlinedSparkles)
            ->color('primary')
            ->modalHeading(__('resources/ai_agents.test_heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('resources/ai_agents.close'))
            ->modalContent(function (AiAgent $record) {
                $review = Review::query()->latest('created_at_external')->first();

                if ($review === null) {
                    return view('filament.app.ai-agent-preview', [
                        'review' => null,
                        'reply' => __('resources/ai_agents.no_reviews_to_test'),
                    ]);
                }

                try {
                    $generated = app(ReplyGenerator::class)->generate(
                        reviewText: (string) $review->text,
                        rating: (int) $review->rating,
                        authorName: $review->author_name,
                        businessName: (string) ($review->location?->name ?? 'our business'),
                        tone: $record->tone,
                        instruction: $record->instructions(),
                        language: $record->reply_native_language ? null : 'English',
                    );
                    $reply = $generated->text;
                } catch (\Throwable $e) {
                    $reply = __('resources/ai_agents.generation_failed', ['error' => $e->getMessage()]);
                }

                return view('filament.app.ai-agent-preview', [
                    'review' => $review,
                    'reply' => $reply,
                ]);
            });
    }
}
