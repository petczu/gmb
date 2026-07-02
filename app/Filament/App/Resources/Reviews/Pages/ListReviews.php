<?php

namespace App\Filament\App\Resources\Reviews\Pages;

use App\Filament\App\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Resources\Pages\ListRecords;

class ListReviews extends ListRecords
{
    protected static string $resource = ReviewResource::class;

    /**
     * Deep-link from the "new reviews" email: ?review={id} opens the reply
     * slide-over for that review. We set Filament's $defaultAction, which the
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
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
