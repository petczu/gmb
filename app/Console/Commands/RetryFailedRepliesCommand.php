<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AutoReplyQueueItem;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use App\Support\ReplyFailure;
use Illuminate\Console\Command;
use Throwable;

/**
 * Re-post replies whose publishing failed for a transient reason (Google/Zernio
 * "try again later", rate limiting). Only failed queue items that already have
 * a drafted reply and whose review is still unanswered are retried; structural
 * failures (review/location gone, unauthorized) are skipped so we don't loop on
 * something that can't succeed. Bounded to recent failures via --days.
 */
class RetryFailedRepliesCommand extends Command
{
    protected $signature = 'auto-reply:retry-failed
        {workspace? : Workspace id or slug; omit for all}
        {--days=14 : Only retry failures from the last N days}
        {--dry-run : List what would be retried without posting}';

    protected $description = 'Re-post replies that failed to publish for a transient reason';

    public function handle(): int
    {
        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $this->retryForWorkspace($workspace);
            } finally {
                $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
            }
        }

        return self::SUCCESS;
    }

    private function retryForWorkspace(Workspace $workspace): void
    {
        $dry = (bool) $this->option('dry-run');
        $since = now()->subDays(max(1, (int) $this->option('days')));

        $items = AutoReplyQueueItem::query()
            ->where('status', 'failed')
            ->whereNotNull('generated_text')->where('generated_text', '!=', '')
            ->where('updated_at', '>=', $since)
            ->with('review')
            ->get()
            ->filter(fn (AutoReplyQueueItem $i): bool => $i->review !== null
                && $i->review->reply_text === null
                && ReplyFailure::isRetryable($i->error));

        if ($items->isEmpty()) {
            return;
        }

        $service = app(AutomationService::class);
        $ok = 0;
        $failed = 0;

        foreach ($items as $item) {
            if ($dry) {
                $this->line(sprintf('  · [dry-run] would retry review #%d', $item->review_id));

                continue;
            }

            try {
                $result = $service->retry($workspace, $item->review);
                $result !== null && $result->status === 'published' ? $ok++ : $failed++;
            } catch (Throwable $e) {
                $item->forceFill(['error' => ReplyFailure::humanize($e)])->save();
                $failed++;
            }
        }

        $this->info(sprintf('%s: %d retried%s, %d still failing.', $workspace->slug ?? $workspace->id, $ok, $dry ? ' (dry-run)' : '', $failed));
    }
}
