<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Filament\App\Resources\Locations\HoursBulkEdit;
use App\Models\Location;
use App\Models\ScheduledListingUpdate;
use App\Models\Workspace;
use App\Services\ActivityLog\ActivityLogger;
use Illuminate\Console\Command;

/**
 * Pushes hours edits that were scheduled for a future date (see the Edit
 * hours modal on Locations) once their apply_on day arrives. Runs early each
 * morning so "new schedule from Jan 1" is live before opening time.
 */
class ListingsApplyScheduledCommand extends Command
{
    protected $signature = 'listings:apply-scheduled {workspace? : Workspace id or slug; omit for all}';

    protected $description = 'Apply scheduled bulk hours updates that are due';

    public function handle(): int
    {
        $workspaces = $this->argument('workspace') === null
            ? Workspace::query()->get()
            : Workspace::query()->where('id', $this->argument('workspace'))->orWhere('slug', $this->argument('workspace'))->get();

        foreach ($workspaces as $workspace) {
            $previous = tenant();
            tenancy()->initialize($workspace);

            try {
                $applied = $this->applyDue();

                if ($applied > 0) {
                    $this->info(sprintf('[%s] applied %d', $workspace->slug, $applied));
                }
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

    private function applyDue(): int
    {
        $due = ScheduledListingUpdate::query()
            ->whereNull('applied_at')
            ->where('apply_on', '<=', today()->toDateString())
            ->get();

        foreach ($due as $update) {
            $locations = Location::query()->whereIn('id', $update->location_ids ?? [])->get();

            [$updated, $failed] = HoursBulkEdit::push(
                $locations,
                $update->opening_hours,
                $update->special_hours,
            );

            $update->forceFill([
                'applied_at' => now(),
                'error' => $failed !== [] ? mb_substr(implode("\n", $failed), 0, 2000) : null,
            ])->save();

            ActivityLogger::log('listing.scheduled_hours_applied', [
                'locations' => $updated,
                'failed' => count($failed),
                'apply_on' => $update->apply_on->toDateString(),
            ]);
        }

        return $due->count();
    }
}
