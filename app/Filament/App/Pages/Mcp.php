<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Billing\Plans;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Mcp extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 84;

    // The /mcp URL is the MCP server endpoint (routes/ai.php); keep this page off it.
    protected static ?string $slug = 'mcp-settings';

    protected string $view = 'filament.app.pages.mcp';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('nav.mcp');
    }

    public function getTitle(): string
    {
        return __('nav.mcp');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_integrations') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_integrations') ?? false;
    }

    protected function workspace(): Workspace
    {
        return once(fn () => Workspace::findOrFail(session('current_workspace_id')));
    }

    public function isPro(): bool
    {
        return app(LocationBilling::class)->allows($this->workspace(), Plans::MCP);
    }

    /** The MCP endpoint the user pastes into their AI client. */
    public function endpoint(): string
    {
        return rtrim((string) config('app.url'), '/').'/mcp';
    }

    public function mount(): void
    {
        $this->form->fill(['write_enabled' => $this->workspace()->mcpWriteEnabled()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('pages/mcp.write_section'))
                    ->description(__('pages/mcp.write_section_desc'))
                    ->schema([
                        Toggle::make('write_enabled')
                            ->label(__('pages/mcp.write_toggle'))
                            ->helperText(__('pages/mcp.write_toggle_help'))
                            ->disabled(! $this->isPro()),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn (): bool => $this->isPro())
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $workspace = $this->workspace();
        $workspace->setAttribute('mcp_write_enabled', (bool) ($this->form->getState()['write_enabled'] ?? false));
        $workspace->save();

        Notification::make()->title(__('pages/mcp.saved'))->success()->send();
    }
}
