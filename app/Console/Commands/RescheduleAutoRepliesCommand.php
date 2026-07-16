<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AutoReplyQueueItem;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use App\Services\Ai\ReplyScheduler;
use Illuminate\Console\Command;

/**
 * Recomputes post_at for already-scheduled auto-replies using the current
 * working-hours logic. One-off catch-up after the overnight-window fix: replies
 * that were wrongly deferred (and bunched at a window's opening) get spread
 * correctly again. The matching automation is re-derived per review.
 */
class RescheduleAutoRepliesCommand extends Command
{
    protected $signature = 'auto-reply:reschedule {workspace? : Workspace id or slug; omit for all}';

    protected $description = 'Recompute post_at for scheduled auto-replies using the current working-hours logic';

    public function handle(AutomationService $automations, ReplyScheduler $scheduler): int
    {
        $arg = $this->argument('workspace');
        $workspaces = $arg !== null
            ? Workspace::query()->where('id', $arg)->orWhere('slug', $arg)->get()
            : Workspace::query()->get();

        if ($workspaces->isEmpty()) {
            $this->warn('No matching workspaces.');

            return self::SUCCESS;
        }

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $updated = 0;
                AutoReplyQueueItem::query()
                    ->where('status', 'scheduled')
                    ->with('review.location')
                    ->get()
                    ->each(function (AutoReplyQueueItem $item) use ($automations, $scheduler, $workspace, &$updated): void {
                        $review = $item->review;
                        if ($review === null) {
                            return;
                        }

                        $automation = $automations->matching($review);
                        if ($automation === null) {
                            return;
                        }

                        $tz = $automations->timezoneFor($workspace, $review);
                        $item->forceFill(['post_at' => $scheduler->scheduleFor($automation, now(), $tz)])->save();
                        $updated++;
                    });

                $this->line("[{$workspace->slug}] rescheduled {$updated}");
            } finally {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        return self::SUCCESS;
    }
}
