<?php

declare(strict_types=1);

namespace App\Services\Reviews;

use App\Models\Workspace;

/**
 * Resolves the active ReviewProvider. There is one platform-wide Zernio key in
 * ENV (config services.reviews.zernio_key) — not a per-workspace token — so the
 * provider is workspace-agnostic.
 */
class ReviewProviderFactory
{
    public function make(): ReviewProvider
    {
        if (config('services.reviews.driver') === 'zernio') {
            return new ZernioProvider(config('services.reviews.zernio_key'));
        }

        return new FakeReviewProvider();
    }

    /**
     * Back-compat helper; workspace is ignored (single shared key).
     */
    public function for(?Workspace $workspace = null): ReviewProvider
    {
        return $this->make();
    }
}
