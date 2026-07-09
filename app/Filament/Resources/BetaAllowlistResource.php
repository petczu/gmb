<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BetaAllowlistResource\Pages;
use App\Models\BetaAllowlistEntry;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Emails that skip the private beta application queue (super-admin panel):
 * a sign-up with a listed address is activated immediately, with the normal
 * welcome + workspace flow. Matching is case-insensitive (stored lowercased).
 */
class BetaAllowlistResource extends Resource
{
    protected static ?string $model = BetaAllowlistEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Beta allowlist';

    protected static ?string $modelLabel = 'allowlisted email';

    protected static ?string $slug = 'beta-allowlist';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Sign-ups with this email skip the beta application and start right away.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('created_at')->label('Added')->since()->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBetaAllowlist::route('/'),
        ];
    }
}
