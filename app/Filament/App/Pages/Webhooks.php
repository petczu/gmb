<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Webhooks\WebhookEvents;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class Webhooks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 83;

    protected static ?string $slug = 'webhooks';

    protected string $view = 'filament.app.pages.webhooks';

    public static function getNavigationLabel(): string
    {
        return __('nav.webhooks');
    }

    public function getTitle(): string
    {
        return __('nav.webhooks');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('manage_team') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_team') ?? false;
    }

    public function isPro(): bool
    {
        $workspace = \App\Models\Workspace::find(session('current_workspace_id'));

        return $workspace !== null
            && app(\App\Services\Billing\LocationBilling::class)->allows($workspace, \App\Billing\Plans::API);
    }

    /**
     * Re-queue a delivery from the history modal. Called via wire:click.
     */
    public function resendDelivery(int $deliveryId): void
    {
        $delivery = WebhookDelivery::find($deliveryId);

        if ($delivery === null) {
            return;
        }

        $delivery->forceFill(['status' => WebhookDelivery::STATUS_PENDING])->save();
        SendWebhookJob::dispatch((string) session('current_workspace_id'), $delivery->id);

        Notification::make()->title(__('pages/webhooks.resent'))->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => WebhookEndpoint::query())
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/webhooks.empty'))
            ->columns([
                TextColumn::make('url')->label(__('pages/webhooks.col_url'))
                    ->limit(48)->fontFamily('mono')->tooltip(fn (WebhookEndpoint $r): string => $r->url),
                TextColumn::make('events')->label(__('pages/webhooks.col_events'))->badge(),
                IconColumn::make('active')->label(__('pages/webhooks.col_active'))->boolean(),
                TextColumn::make('last_triggered_at')->label(__('pages/webhooks.col_last'))
                    ->since()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('deliveries')
                    ->label(__('pages/webhooks.deliveries'))
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->modalHeading(__('pages/webhooks.deliveries_heading'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('common.close'))
                    ->modalContent(fn (WebhookEndpoint $record): View => view('filament.app.webhooks.deliveries', [
                        'deliveries' => $record->deliveries()->latest()->limit(20)->get(),
                    ])),

                Action::make('secret')
                    ->label(__('pages/webhooks.secret'))
                    ->icon(Heroicon::OutlinedKey)
                    ->color('gray')
                    ->modalHeading(__('pages/webhooks.secret_heading'))
                    ->modalDescription(__('pages/webhooks.secret_desc'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('common.close'))
                    ->modalContent(fn (WebhookEndpoint $record): View => view('filament.app.webhooks.secret', [
                        'secret' => $record->secret,
                    ])),

                Action::make('edit')
                    ->label(__('pages/webhooks.edit'))
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->fillForm(fn (WebhookEndpoint $record): array => [
                        'name' => $record->name,
                        'url' => $record->url,
                        'events' => $record->events,
                        'active' => $record->active,
                    ])
                    ->schema($this->formSchema())
                    ->action(function (WebhookEndpoint $record, array $data): void {
                        $record->update($this->sanitize($data));
                        Notification::make()->title(__('pages/webhooks.saved'))->success()->send();
                    }),

                Action::make('delete')
                    ->label(__('pages/webhooks.delete'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (WebhookEndpoint $record): void {
                        $record->delete();
                        Notification::make()->title(__('pages/webhooks.deleted'))->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('pages/webhooks.create'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => $this->isPro())
                    ->modalHeading(__('pages/webhooks.create_heading'))
                    ->schema($this->formSchema())
                    ->action(function (array $data): void {
                        WebhookEndpoint::create($this->sanitize($data));
                        Notification::make()->title(__('pages/webhooks.created'))->success()->send();
                    }),
            ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Field>
     */
    protected function formSchema(): array
    {
        return [
            TextInput::make('name')->label(__('pages/webhooks.field_name'))->maxLength(120),
            TextInput::make('url')->label(__('pages/webhooks.field_url'))->url()->required()->maxLength(2048)
                ->placeholder('https://example.com/webhooks/reviews'),
            CheckboxList::make('events')
                ->label(__('pages/webhooks.field_events'))
                ->options(WebhookEvents::options())
                ->required()
                ->columns(1),
            Toggle::make('active')->label(__('pages/webhooks.field_active'))->default(true),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function sanitize(array $data): array
    {
        return [
            'name' => $data['name'] ?? null,
            'url' => $data['url'],
            'events' => array_values(array_filter(
                $data['events'] ?? [],
                fn (string $e): bool => WebhookEvents::isValid($e),
            )),
            'active' => (bool) ($data['active'] ?? true),
        ];
    }
}
