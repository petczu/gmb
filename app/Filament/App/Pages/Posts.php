<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Models\Location;
use App\Models\Post;
use App\Services\ActivityLog\ActivityLogger;
use App\Services\Posts\PostPublisher;
use App\Services\Zernio\ZernioRestClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

/**
 * Google Business Profile posts (updates, offers, events, photos), published
 * through Zernio's content publishing API. Zernio handles scheduling, so each
 * row here is history — not a local delivery queue.
 */
class Posts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'posts';

    protected string $view = 'filament.app.pages.posts';

    public static function getNavigationLabel(): string
    {
        return __('pages/posts.nav');
    }

    public function getTitle(): string
    {
        return __('pages/posts.title');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return tenancy()->initialized && (auth()->user()?->can('publish_posts') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('publish_posts') ?? false;
    }

    public function isConfigured(): bool
    {
        return app(ZernioRestClient::class)->configured();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Post::query())
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('pages/posts.empty'))
            ->emptyStateDescription(__('pages/posts.empty_desc'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('pages/posts.col_created'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('pages/posts.col_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('pages/posts.type_'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'offer' => 'warning',
                        'event' => 'info',
                        'photo' => 'gray',
                        default => 'primary',
                    }),

                TextColumn::make('caption')
                    ->label(__('pages/posts.col_caption'))
                    ->limit(60)
                    ->placeholder('—')
                    ->tooltip(fn (Post $record): ?string => $record->caption),

                TextColumn::make('location_ids')
                    ->label(__('pages/posts.col_locations'))
                    ->state(fn (Post $record): string => (string) count($record->location_ids ?? [])),

                TextColumn::make('status')
                    ->label(__('pages/posts.col_status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('pages/posts.status_'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'info',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->tooltip(fn (Post $record): ?string => $record->error),

                TextColumn::make('scheduled_at')
                    ->label(__('pages/posts.col_scheduled'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('pages/posts.col_status'))
                    ->options([
                        'published' => __('pages/posts.status_published'),
                        'scheduled' => __('pages/posts.status_scheduled'),
                        'in_progress' => __('pages/posts.status_in_progress'),
                        'failed' => __('pages/posts.status_failed'),
                    ]),
            ])
            ->recordActions([
                Action::make('delete')
                    ->label(__('pages/posts.delete'))
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('pages/posts.delete_desc'))
                    ->action(function (Post $record): void {
                        $record->delete();
                        Notification::make()->title(__('pages/posts.deleted'))->success()->send();
                    }),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('pages/posts.create'))
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => $this->isConfigured())
                    ->modalHeading(__('pages/posts.create_heading'))
                    ->modalSubmitActionLabel(__('pages/posts.submit'))
                    ->schema($this->formSchema())
                    ->action(fn (array $data) => $this->publish($data)),
            ]);
    }

    /**
     * @return array<int, Field>
     */
    protected function formSchema(): array
    {
        $isOfferOrEvent = fn (Get $get): bool => in_array($get('type'), ['offer', 'event'], true);

        return [
            Select::make('type')
                ->label(__('pages/posts.field_type'))
                // Zernio's native API models a photo post as a STANDARD update
                // with an image, so only the three real GBP topic types remain.
                ->options(collect(['update', 'offer', 'event'])->mapWithKeys(
                    fn (string $t): array => [$t => __('pages/posts.type_'.$t)],
                )->all())
                ->default('update')
                ->required()
                ->live()
                ->selectablePlaceholder(false),

            Select::make('locations')
                ->label(__('pages/posts.field_locations'))
                ->multiple()
                ->options(fn (): array => Location::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default(fn (): array => Location::query()->pluck('id')->all())
                ->required(),

            Textarea::make('caption')
                ->label(__('pages/posts.field_caption'))
                ->rows(4)
                ->maxLength(1500)
                ->required(),

            FileUpload::make('image')
                ->label(__('pages/posts.field_image'))
                ->image()
                ->disk('uploads')
                ->directory('posts')
                ->maxSize(4096)
                ->helperText(__('pages/posts.field_image_helper')),

            TextInput::make('title')
                ->label(__('pages/posts.field_title'))
                ->maxLength(58)
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent),

            DateTimePicker::make('starts_at')
                ->label(__('pages/posts.field_starts'))
                ->seconds(false)
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent),

            DateTimePicker::make('ends_at')
                ->label(__('pages/posts.field_ends'))
                ->seconds(false)
                ->after('starts_at')
                ->required($isOfferOrEvent)
                ->visible($isOfferOrEvent),

            TextInput::make('voucher_code')
                ->label(__('pages/posts.field_voucher'))
                ->maxLength(58)
                ->visible(fn (Get $get): bool => $get('type') === 'offer'),

            TextInput::make('redeem_url')
                ->label(__('pages/posts.field_redeem_url'))
                ->url()
                ->visible(fn (Get $get): bool => $get('type') === 'offer'),

            TextInput::make('terms_url')
                ->label(__('pages/posts.field_terms_url'))
                ->url()
                ->visible(fn (Get $get): bool => $get('type') === 'offer'),

            Select::make('cta_type')
                ->label(__('pages/posts.field_cta'))
                ->options(collect(Post::CTA_TYPES)->mapWithKeys(
                    fn (string $t): array => [$t => __('pages/posts.cta_'.$t)],
                )->all())
                ->placeholder(__('pages/posts.cta_none'))
                ->live()
                ->visible(fn (Get $get): bool => in_array($get('type'), ['update', 'event'], true)),

            TextInput::make('cta_url')
                ->label(__('pages/posts.field_cta_url'))
                ->url()
                ->required(fn (Get $get): bool => filled($get('cta_type')) && $get('cta_type') !== 'call')
                ->visible(fn (Get $get): bool => filled($get('cta_type')) && $get('cta_type') !== 'call'
                    && in_array($get('type'), ['update', 'event'], true)),

            DateTimePicker::make('scheduled_at')
                ->label(__('pages/posts.field_schedule'))
                ->seconds(false)
                ->minDate(now())
                ->helperText(__('pages/posts.field_schedule_helper')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function publish(array $data): void
    {
        $locations = Location::query()->whereIn('id', $data['locations'] ?? [])->get();

        if ($locations->isEmpty()) {
            Notification::make()->title(__('pages/posts.no_locations'))->danger()->send();

            return;
        }

        // Native posting targets the Zernio account + GBP location ids the
        // locations were connected with — no extra id mapping.
        $unmatched = $locations->filter(fn (Location $l): bool => blank($l->zernio_account_id) || blank($l->external_id));

        if ($unmatched->isNotEmpty()) {
            Notification::make()
                ->title(__('pages/posts.unmatched'))
                ->body($unmatched->pluck('name')->implode(', '))
                ->danger()
                ->send();

            return;
        }

        $post = Post::create([
            'type' => $data['type'],
            'caption' => $data['caption'] ?? null,
            'title' => $data['title'] ?? null,
            'cta_type' => $data['cta_type'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'image_url' => filled($data['image'] ?? null)
                ? url(Storage::disk('uploads')->url($data['image']))
                : null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'voucher_code' => $data['voucher_code'] ?? null,
            'redeem_url' => $data['redeem_url'] ?? null,
            'terms_url' => $data['terms_url'] ?? null,
            'location_ids' => $locations->pluck('id')->all(),
            'source_ids' => $locations->pluck('external_id')->all(),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => 'in_progress',
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()?->name,
        ]);

        app(PostPublisher::class)->publish($post, $locations);
        $post->refresh();

        if ($post->status === 'failed') {
            Notification::make()
                ->title(__('pages/posts.publish_failed'))
                ->body((string) $post->error)
                ->danger()
                ->send();

            return;
        }

        ActivityLogger::log(
            $post->status === 'scheduled' ? 'post.scheduled' : 'post.published',
            ['type' => $post->type, 'locations' => count($post->location_ids)],
            $post,
        );

        Notification::make()
            ->title($post->status === 'scheduled' ? __('pages/posts.scheduled_ok') : __('pages/posts.published_ok'))
            ->success()
            ->send();
    }
}
