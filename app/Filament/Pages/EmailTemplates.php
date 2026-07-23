<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Mail\Templates\EmailTemplateCatalog;
use App\Mail\Templates\EmailTemplateRenderer;
use App\Models\EmailTemplate;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;

/**
 * Super-admin editor for all transactional email templates: pick a template,
 * switch language, edit the markdown body + subject, and see a live branded
 * preview. Edits are stored in email_templates and used by every send.
 */
class EmailTemplates extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $slug = 'email-templates';

    protected string $view = 'filament.pages.email-templates';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'Email templates';
    }

    public function getTitle(): string
    {
        return 'Email templates';
    }

    public function mount(): void
    {
        $key = EmailTemplateCatalog::keys()[0];
        $this->form->fill([
            'templateKey' => $key,
            'locale' => 'en',
        ] + $this->load($key, 'en'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make()
                    ->schema([
                        Select::make('templateKey')
                            ->label('Template')
                            ->options($this->templateOptions())
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set, callable $get) => $this->reload($set, $get)),
                        ToggleButtons::make('locale')
                            ->label('Language')
                            ->options(Locales::options())
                            ->inline()
                            ->live()
                            ->afterStateUpdated(fn (callable $set, callable $get) => $this->reload($set, $get)),
                    ])
                    ->columns(2),

                Section::make()
                    ->schema([
                        TextInput::make('subject')
                            ->label('Subject')
                            ->required()
                            ->live(onBlur: true),
                        Textarea::make('body')
                            ->label('Body (markdown)')
                            ->rows(18)
                            ->required()
                            ->live(onBlur: true)
                            ->hint(fn (callable $get): string => 'Placeholders: '.implode('  ', EmailTemplateCatalog::placeholders($get('templateKey'))).'   {{ button:Label }}'),
                    ]),
            ]);
    }

    /** Reload subject + body when the template or language changes. */
    protected function reload(callable $set, callable $get): void
    {
        $loaded = $this->load($get('templateKey'), $get('locale'));
        $set('subject', $loaded['subject']);
        $set('body', $loaded['body']);
    }

    /**
     * The stored row for key+locale, or the catalogue default if none yet.
     *
     * @return array{subject: string, body: string}
     */
    protected function load(string $key, string $locale): array
    {
        $row = EmailTemplate::query()->where('key', $key)->where('locale', $locale)->first();

        return [
            'subject' => $row?->subject ?? EmailTemplateCatalog::defaultSubject($key, $locale),
            'body' => $row?->body ?? EmailTemplateCatalog::defaultBody($key, $locale),
        ];
    }

    /** @return array<string, array<string, string>> grouped by category */
    protected function templateOptions(): array
    {
        $grouped = [];
        foreach (EmailTemplateCatalog::all() as $key => $meta) {
            $grouped[$meta['category']][$key] = $meta['title'];
        }

        return $grouped;
    }

    /** Live preview HTML of the current (unsaved) body with sample data. */
    public function previewHtml(): string
    {
        $key = (string) ($this->data['templateKey'] ?? EmailTemplateCatalog::keys()[0]);

        return app(EmailTemplateRenderer::class)->preview(
            (string) ($this->data['body'] ?? ''),
            EmailTemplateCatalog::sample($key),
            EmailTemplateCatalog::sampleBlocks($key, (string) ($this->data['locale'] ?? 'en')),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->icon(Heroicon::OutlinedCheck)
                ->action('save'),

            Action::make('sendTest')
                ->label('Send test to me')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->action('sendTest'),

            Action::make('reset')
                ->label('Reset to default')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Reset to default?')
                ->modalDescription('This restores the shipped copy for this template and language. It does not save until you click Save.')
                ->action('resetToDefault'),
        ];
    }

    public function save(): void
    {
        $key = (string) $this->data['templateKey'];
        $locale = (string) $this->data['locale'];

        EmailTemplate::query()->updateOrCreate(
            ['key' => $key, 'locale' => $locale],
            ['subject' => (string) $this->data['subject'], 'body' => (string) $this->data['body']],
        );

        Notification::make()->title('Template saved')->success()->send();
    }

    public function resetToDefault(): void
    {
        $key = (string) $this->data['templateKey'];
        $locale = (string) $this->data['locale'];

        $this->data['subject'] = EmailTemplateCatalog::defaultSubject($key, $locale);
        $this->data['body'] = EmailTemplateCatalog::defaultBody($key, $locale);

        Notification::make()->title('Restored default — click Save to keep it')->success()->send();
    }

    public function sendTest(): void
    {
        $key = (string) $this->data['templateKey'];
        $locale = (string) $this->data['locale'];
        $sample = EmailTemplateCatalog::sample($key);
        $blocks = EmailTemplateCatalog::sampleBlocks($key, $locale);
        $renderer = app(EmailTemplateRenderer::class);

        $html = $renderer->preview((string) $this->data['body'], $sample, $blocks);
        $subject = $renderer->preview((string) $this->data['subject'], $sample);
        $subject = trim(strip_tags($subject));
        $email = (string) auth()->user()->email;

        Mail::html($html, function ($message) use ($email, $subject): void {
            $message->to($email)->subject('[Test] '.$subject);
        });

        Notification::make()->title('Test email sent to '.$email)->success()->send();
    }
}
