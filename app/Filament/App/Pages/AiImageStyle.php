<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Workspace;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Settings page for AI-generated post images: reference designs, brand style
 * notes, base look and headline rules. Values live on the workspace and are
 * read by GeneratePostImagesJob on every generation.
 */
class AiImageStyle extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 71;

    protected static ?string $slug = 'ai-images';

    protected string $view = 'filament.app.pages.company';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('pages/company.ai_image_section');
    }

    public function getTitle(): string
    {
        return __('pages/company.ai_image_section');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_company') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    public function mount(): void
    {
        $w = $this->workspace();

        $this->form->fill([
            'ai_image_refs' => $w->ai_image_refs ?? [],
            'ai_image_notes' => $w->ai_image_notes,
            'ai_image_base_style' => $w->ai_image_base_style ?? 'photo',
            'ai_image_headline' => (bool) ($w->ai_image_headline ?? true),
            'ai_image_headline_words' => (int) ($w->ai_image_headline_words ?? 5),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('pages/company.ai_image_section'))
                    ->description(__('pages/company.ai_image_desc'))
                    ->schema([
                        FileUpload::make('ai_image_refs')
                            ->label(__('pages/company.ai_image_refs'))
                            ->helperText(__('pages/company.ai_image_refs_helper'))
                            ->image()
                            ->multiple()
                            ->maxFiles(3)
                            ->disk('uploads')
                            ->directory('image-style')
                            ->imagePreviewHeight('80')
                            ->columnSpanFull(),
                        Textarea::make('ai_image_notes')
                            ->label(__('pages/company.ai_image_notes'))
                            ->placeholder(__('pages/company.ai_image_notes_ph'))
                            ->rows(3)
                            ->maxLength(600)
                            ->columnSpanFull(),
                        Select::make('ai_image_base_style')
                            ->label(__('pages/company.ai_image_style'))
                            ->options([
                                'photo' => __('pages/company.ai_image_style_photo'),
                                'reference' => __('pages/company.ai_image_style_reference'),
                                'illustration' => __('pages/company.ai_image_style_illustration'),
                                'minimal' => __('pages/company.ai_image_style_minimal'),
                            ])
                            ->native(false)
                            ->selectablePlaceholder(false),
                        Toggle::make('ai_image_headline')
                            ->label(__('pages/company.ai_image_headline'))
                            ->inline(false)
                            ->live(),
                        TextInput::make('ai_image_headline_words')
                            ->label(__('pages/company.ai_image_headline_words'))
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(8)
                            ->visible(fn (Get $get): bool => (bool) $get('ai_image_headline')),
                    ])
                    ->columns(3),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $w = $this->workspace();

        foreach (['ai_image_refs', 'ai_image_notes', 'ai_image_base_style', 'ai_image_headline', 'ai_image_headline_words'] as $key) {
            $w->{$key} = $state[$key] ?? null;
        }
        $w->save();

        Notification::make()->title(__('pages/company.settings_saved'))->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->icon(Heroicon::OutlinedCheck)
                ->action('save'),
        ];
    }
}
