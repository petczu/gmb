<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Api\ApiAbilities;
use App\Billing\Plans;
use App\Models\ApiKey;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Billing\LocationBilling;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApiKeys extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 82;

    protected static ?string $slug = 'api-keys';

    protected string $view = 'filament.app.pages.api-keys';

    /** Raw key shown exactly once, right after creation. */
    public ?string $plainKey = null;

    public static function getNavigationLabel(): string
    {
        return __('nav.api_keys');
    }

    public function getTitle(): string
    {
        return __('nav.api_keys');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_integrations') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_integrations') ?? false;
    }

    protected function workspaceId(): string
    {
        return (string) session('current_workspace_id');
    }

    public function isPro(): bool
    {
        $workspace = Workspace::find($this->workspaceId());

        return $workspace !== null && app(LocationBilling::class)->allows($workspace, Plans::API);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ApiKey::query()->where('workspace_id', $this->workspaceId()))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/api_keys.empty'))
            ->columns([
                TextColumn::make('name')->label(__('pages/api_keys.col_name'))->weight('medium'),
                TextColumn::make('prefix')->label(__('pages/api_keys.col_key'))
                    ->formatStateUsing(fn (string $state): string => $state.'…')
                    ->fontFamily('mono'),
                TextColumn::make('abilities')->label(__('pages/api_keys.col_scopes'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state),
                TextColumn::make('last_used_at')->label(__('pages/api_keys.col_last_used'))
                    ->since()->placeholder(__('pages/api_keys.never_used')),
                TextColumn::make('created_at')->label(__('pages/api_keys.col_created'))->date(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label(__('pages/api_keys.edit'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading(__('pages/api_keys.edit_heading'))
                    ->fillForm(fn (ApiKey $record): array => [
                        'name' => $record->name,
                        'abilities' => $record->abilities,
                    ])
                    ->schema([
                        TextInput::make('name')->label(__('pages/api_keys.field_name'))->required()->maxLength(120),
                        CheckboxList::make('abilities')
                            ->label(__('pages/api_keys.field_scopes'))
                            ->options(ApiAbilities::options())
                            ->required()
                            ->columns(1),
                    ])
                    ->action(function (ApiKey $record, array $data): void {
                        $abilities = array_values(array_filter(
                            $data['abilities'] ?? [],
                            fn (string $a): bool => ApiAbilities::isValid($a),
                        ));

                        if ($abilities === []) {
                            Notification::make()->title(__('pages/api_keys.need_scope'))->danger()->send();

                            return;
                        }

                        $record->update([
                            'name' => $data['name'],
                            'abilities' => $abilities,
                        ]);

                        Notification::make()->title(__('pages/api_keys.updated'))->success()->send();
                    }),

                Action::make('revoke')
                    ->label(__('pages/api_keys.revoke'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('pages/api_keys.revoke_heading'))
                    ->modalDescription(__('pages/api_keys.revoke_desc'))
                    ->action(function (ApiKey $record): void {
                        ActivityLogger::log('apikey.revoked', ['name' => $record->name]);
                        $record->delete();
                        Notification::make()->title(__('pages/api_keys.revoked'))->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('pages/api_keys.create'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => $this->isPro())
                    ->modalHeading(__('pages/api_keys.create_heading'))
                    ->schema([
                        TextInput::make('name')->label(__('pages/api_keys.field_name'))->required()->maxLength(120),
                        CheckboxList::make('abilities')
                            ->label(__('pages/api_keys.field_scopes'))
                            ->options(ApiAbilities::options())
                            ->required()
                            ->columns(1),
                        Select::make('expires')->label(__('pages/api_keys.field_expires'))
                            ->options([
                                '' => __('pages/api_keys.expires_never'),
                                '30' => __('pages/api_keys.expires_days', ['days' => 30]),
                                '90' => __('pages/api_keys.expires_days', ['days' => 90]),
                                '365' => __('pages/api_keys.expires_days', ['days' => 365]),
                            ])
                            ->default('')
                            ->selectablePlaceholder(false),
                    ])
                    ->action(function (array $data): void {
                        $abilities = array_values(array_filter(
                            $data['abilities'] ?? [],
                            fn (string $a): bool => ApiAbilities::isValid($a),
                        ));

                        if ($abilities === []) {
                            Notification::make()->title(__('pages/api_keys.need_scope'))->danger()->send();

                            return;
                        }

                        $expiresAt = ! empty($data['expires'])
                            ? now()->addDays((int) $data['expires'])
                            : null;

                        [, $raw] = ApiKey::generate($this->workspaceId(), $data['name'], $abilities, $expiresAt);

                        ActivityLogger::log('apikey.created', ['name' => $data['name'], 'scopes' => $abilities]);

                        $this->plainKey = $raw;
                        Notification::make()->title(__('pages/api_keys.created'))->success()->send();
                    }),
            ]);
    }
}
