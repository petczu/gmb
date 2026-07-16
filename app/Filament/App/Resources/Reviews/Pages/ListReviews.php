<?php

namespace App\Filament\App\Resources\Reviews\Pages;

use App\Filament\App\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    /**
     * Review ids the multi-review digest email deep-linked to (?reviews=1,2,3).
     * When set, the table shows ONLY these, with a dismissible banner.
     *
     * @var list<int>
     */
    public array $emailReviewIds = [];

    /**
     * Deep-link from the "new reviews" email:
     *  - ?review={id}    opens the reply slide-over for that one review,
     *  - ?reviews=1,2,3  filters the list to exactly those new reviews.
     * The single-review action is set via Filament's $defaultAction, which the
     * page renders as a wire:init="mountAction(...)" — so it mounts client-side
     * once the table is booted (mounting it here on the server is too early).
     */
    public function mount(): void
    {
        parent::mount();

        $reviewId = request()->query('review');
        if ($reviewId && Review::query()->whereKey($reviewId)->exists()) {
            $this->defaultAction = 'reply';
            $this->defaultActionContext = ['table' => true, 'recordKey' => (string) $reviewId];

            return;
        }

        $ids = array_filter(array_map('intval', explode(',', (string) request()->query('reviews'))));
        if ($ids !== []) {
            $this->emailReviewIds = Review::query()->whereIn('id', $ids)->pluck('id')->all();
        }
    }

    /** Clear the email filter and show all reviews again. */
    public function clearEmailFilter(): void
    {
        $this->emailReviewIds = [];
        $this->resetTable();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Drop the default tabs block that renders ABOVE the table — the tabs are
     * rendered inside the table's own header slot instead (ReviewsTable
     * ->header()). Tab STATE (activeTab + getTabs) still comes from the page.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    /**
     * Status tabs mirroring the auto-reply approval queue: a review's tab
     * reflects the state of its auto-reply (needs approval / scheduled /
     * failed), while "Published" means a reply has actually gone out.
     */
    public function getTabs(): array
    {
        $withQueueStatus = fn (string $status): int => Review::query()
            ->whereHas('queueItems', fn (Builder $query): Builder => $query->where('status', $status))
            ->count();

        return [
            'all' => Tab::make(__('resources/reviews.tab_all')),

            'needs_approval' => Tab::make(__('resources/reviews.tab_needs_approval'))
                ->badge($withQueueStatus('pending') ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('queueItems', fn (Builder $q): Builder => $q->where('status', 'pending'))),

            'scheduled' => Tab::make(__('resources/reviews.tab_scheduled'))
                ->badge($withQueueStatus('scheduled') ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('queueItems', fn (Builder $q): Builder => $q->where('status', 'scheduled'))),

            'published' => Tab::make(__('resources/reviews.tab_published'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('reply_text')),

            'failed' => Tab::make(__('resources/reviews.tab_failed'))
                ->badge($withQueueStatus('failed') ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('queueItems', fn (Builder $q): Builder => $q->where('status', 'failed'))),
        ];
    }
}
