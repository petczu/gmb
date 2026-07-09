<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BetaRequestResource\Pages;
use App\Models\User;
use App\Services\Auth\BetaAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Private beta application queue (super-admin panel): every registered user
 * with their activation state. "Approve" sets users.approved_at and emails
 * the person that their access is ready.
 */
class BetaRequestResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Beta requests';

    protected static ?string $modelLabel = 'beta request';

    protected static ?string $pluralModelLabel = 'beta requests';

    protected static ?string $slug = 'beta-requests';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('created_at')->label('Signed up')->since()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (User $record): string => $record->approved_at !== null ? 'Active' : 'Pending')
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'warning'),
                TextColumn::make('approved_at')->label('Activated')->since()->sortable()->placeholder('Not yet'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'active' => 'Active'])
                    ->default('pending')
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'pending' => $query->whereNull('approved_at'),
                        'active' => $query->whereNotNull('approved_at'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->approved_at === null)
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record): string => 'Activate access for '.$record->email.'? They get an email that their account is ready.')
                    ->action(function (User $record): void {
                        app(BetaAccess::class)->approve($record);

                        Notification::make()
                            ->title('Access activated')
                            ->body('Email sent to '.$record->email)
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBetaRequests::route('/'),
        ];
    }
}
