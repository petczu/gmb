<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\LegalDocument;
use App\Models\User;
use App\Support\AiRateLimit;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Throwable;

use function Laravel\Ai\agent;

/**
 * Super-admin editor for the Terms of Service (EN/DE markdown). "Save" fixes
 * typos silently; "Publish new version" bumps the shared version number, which
 * makes every user re-accept the Terms on their next visit (see
 * EnsureTermsAccepted). The AI assistant rewrites the draft from an
 * instruction ("add a data-retention clause") without publishing anything.
 */
class LegalTerms extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $slug = 'legal-terms';

    protected string $view = 'filament.pages.legal-terms';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'Terms of service';
    }

    public function getTitle(): string
    {
        return 'Terms of service';
    }

    public function getSubheading(): ?string
    {
        $version = LegalDocument::currentVersion(LegalDocument::TERMS);
        $pending = $version > 0
            ? User::query()->where(fn ($q) => $q->whereNull('terms_version')->orWhere('terms_version', '<', $version))->count()
            : 0;

        return "Published version: v{$version} · {$pending} user(s) still to accept it. "
            .'This copy is what users accept in-app; the public page lives on the marketing site (repunio.com/terms) — keep both in sync.';
    }

    public function mount(): void
    {
        $this->form->fill([
            'locale' => 'en',
            'body' => (string) LegalDocument::bodyFor(LegalDocument::TERMS, 'en'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->schema([
                        ToggleButtons::make('locale')
                            ->label('Language')
                            ->options(Locales::options())
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function (?string $state, callable $set): void {
                                $set('body', (string) LegalDocument::bodyFor(LegalDocument::TERMS, $state ?: 'en'));
                            }),

                        Textarea::make('body')
                            ->label('Terms (markdown)')
                            ->rows(26)
                            ->required()
                            ->hintAction(
                                Action::make('aiAssist')
                                    ->label('Edit with AI')
                                    ->icon(Heroicon::OutlinedSparkles)
                                    ->modalHeading('Edit the Terms with AI')
                                    ->modalDescription('Describe the change; the assistant rewrites the draft below. Nothing is saved or published until you do it explicitly.')
                                    ->schema([
                                        Textarea::make('instruction')
                                            ->label('What should change?')
                                            ->placeholder("e.g. Add a clause about data retention: exported reports are kept for 30 days.\nOr: simplify the liability section.")
                                            ->rows(3)
                                            ->required(),
                                    ])
                                    ->action(function (array $data, callable $get, callable $set): void {
                                        $this->aiRewrite((string) $data['instruction'], $get, $set);
                                    }),
                            ),
                    ]),
            ]);
    }

    /** Rewrite the current draft per the admin's instruction (draft only). */
    protected function aiRewrite(string $instruction, callable $get, callable $set): void
    {
        if (AiRateLimit::hit('legal-ai')) {
            Notification::make()->title('Too many generations. Please wait a bit and try again.')->warning()->send();

            return;
        }

        $language = $get('locale') === 'de' ? 'German' : 'English';

        try {
            $response = agent(
                instructions: implode("\n", [
                    'You are a careful legal-document editor for a SaaS product (Repunio, Google review management).',
                    "The document is written in {$language}; keep that language.",
                    'Apply ONLY the requested change; keep the rest of the document intact, including its markdown structure (## headings, paragraphs).',
                    'No em dashes. Output ONLY the full updated markdown document, no commentary, no code fences.',
                ]),
            )->prompt(
                "Instruction: {$instruction}\n\nDocument:\n\n".(string) $get('body'),
                provider: Lab::Anthropic,
                model: (string) config('services.ai.model', 'claude-opus-4-8'),
            );

            $set('body', trim((string) $response->text));
            Notification::make()->title('Draft updated. Review it, then Save or Publish.')->success()->send();
        } catch (Throwable $e) {
            Log::warning('Legal AI edit failed', ['error' => $e->getMessage()]);
            Notification::make()->title('AI edit failed')->body($e->getMessage())->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->color('gray')
                ->action(function (): void {
                    $this->persistBody(bump: false);
                    Notification::make()->title('Saved (no re-acceptance required)')->success()->send();
                }),

            Action::make('publish')
                ->label('Publish new version')
                ->icon(Heroicon::OutlinedMegaphone)
                ->requiresConfirmation()
                ->modalDescription('Every user will have to read and accept the updated Terms before continuing to use the app. Publish?')
                ->action(function (): void {
                    $this->persistBody(bump: true);
                    Notification::make()
                        ->title('Published as v'.LegalDocument::currentVersion(LegalDocument::TERMS))
                        ->body('Users will be asked to accept the new Terms on their next visit.')
                        ->success()
                        ->send();
                }),
        ];
    }

    /** Save the edited locale's body; a bump raises the version on ALL locales. */
    protected function persistBody(bool $bump): void
    {
        $locale = in_array($this->data['locale'] ?? 'en', Locales::codes(), true) ? $this->data['locale'] : 'en';
        $version = max(1, LegalDocument::currentVersion(LegalDocument::TERMS));

        LegalDocument::query()->updateOrCreate(
            ['key' => LegalDocument::TERMS, 'locale' => $locale],
            ['body' => (string) ($this->data['body'] ?? ''), 'version' => $version],
        );

        if ($bump) {
            LegalDocument::query()
                ->where('key', LegalDocument::TERMS)
                ->update(['version' => $version + 1]);
        }
    }
}
