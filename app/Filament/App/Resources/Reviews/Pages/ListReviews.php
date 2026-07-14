<?php

namespace App\Filament\App\Resources\Reviews\Pages;

use App\Filament\App\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Resources\Pages\ListRecords;

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
}
