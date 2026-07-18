<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ApprovalsPendingMail;
use App\Models\AutoReplyQueueItem;
use App\Models\Workspace;
use App\Services\Ai\AutomationService;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Reminds workspace owners about auto-reply queue items that have been waiting
 * for manual approval for more than 24 hours. Sends at most once per day per
 * workspace via a date-stamped dedup flag on the central workspace row.
 */
class ApprovalsReminderCommand extends Command
{
    protected $signature = 'auto-reply:approvals-reminder {workspace? : Workspace id or slug; omit for all}';

    protected $description = 'Remind workspace owners about replies waiting for approval';

    public function handle(): int
    {
        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $this->remindForWorkspace($workspace);
            } finally {
                if ($previous !== null) {
                    tenancy()->initialize($previous);
                } else {
                    tenancy()->end();
                }
            }
        }

        return self::SUCCESS;
    }

    private function remindForWorkspace(Workspace $workspace): void
    {
        $count = AutoReplyQueueItem::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(24))
            ->count();

        if ($count <= 0) {
            return;
        }

        // Once-per-day dedup: an unknown attribute lands in the stancl `data`
        // JSON column, so no migration is needed.
        $flag = 'approvals_reminded_'.now()->format('Y_m_d');

        if ($workspace->getAttribute($flag)) {
            return;
        }

        // Persist the flag before sending so a transient mail failure can't cause
        // a same-day retry storm.
        $workspace->setAttribute($flag, true);
        $workspace->save();

        $samples = AutomationService::pendingApprovalSamples();

        try {
            app(NotificationDispatcher::class)->dispatch(
                $workspace,
                NotificationCategory::OPERATIONS,
                fn (string $name, string $lang) => new ApprovalsPendingMail(
                    name: $name,
                    count: $count,
                    approvalsUrl: rtrim((string) config('app.url'), '/').'/approvals',
                    samples: $samples,
                    lang: $lang,
                ),
            );

            $this->info(sprintf('[%s] reminded of %d pending', $workspace->slug, $count));
        } catch (Throwable $e) {
            Log::warning('Approvals reminder email failed', [
                'workspace' => $workspace->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
