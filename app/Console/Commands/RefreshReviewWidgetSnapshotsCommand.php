<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BuildReviewWidgetSnapshotJob;
use App\Models\ReviewWidget;
use Illuminate\Console\Command;

/**
 * Nightly refresh of every active review widget's snapshot, so organically
 * arriving reviews eventually reach the embed even without a save or a sync.
 */
class RefreshReviewWidgetSnapshotsCommand extends Command
{
    protected $signature = 'widgets:refresh-snapshots';

    protected $description = 'Rebuild the review-showcase widget snapshots';

    public function handle(): int
    {
        $count = 0;

        ReviewWidget::query()
            ->where('active', true)
            ->pluck('id')
            ->each(function (int $id) use (&$count): void {
                BuildReviewWidgetSnapshotJob::dispatch($id);
                $count++;
            });

        $this->info("Queued {$count} widget snapshot rebuild(s).");

        return self::SUCCESS;
    }
}
