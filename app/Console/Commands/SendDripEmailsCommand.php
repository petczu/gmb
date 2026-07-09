<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\DripMail;
use App\Models\Automation;
use App\Models\Competitor;
use App\Models\Location;
use App\Models\ReviewPage;
use App\Models\User;
use App\Services\Onboarding\DripSeries;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDripEmailsCommand extends Command
{
    protected $signature = 'emails:drip {--dry-run : List due emails without sending}';

    protected $description = 'Send the next due onboarding-series email to each user (owners + invited members)';

    public function handle(DripSeries $series): int
    {
        $sent = 0;

        // Only users young enough to still be inside any step window.
        $maxDay = max(array_merge(...array_map('array_values', array_values(DripSeries::TRACKS))));
        $cutoff = now()->subDays($maxDay + DripSeries::WINDOW_DAYS + 1);

        User::query()
            ->where('created_at', '>=', $cutoff)
            ->where('product_emails', true)
            ->cursor()
            ->each(function (User $user) use ($series, &$sent): void {
                $already = DB::connection('mysql')->table('drip_emails')
                    ->where('user_id', $user->id)
                    ->pluck('email_key')
                    ->all();

                // Conditional activation nudges need the real workspace state,
                // but only while at least one of them could still be sent —
                // skip the tenant round-trip once they're all consumed.
                $pendingConditionals = array_diff(array_keys(DripSeries::CONDITIONS), $already) !== [];
                $state = $pendingConditionals ? $this->ownerWorkspaceState($user) : [];

                $key = $series->dueStep($user, $already, state: $state);

                if ($key === null) {
                    return;
                }

                if ($this->option('dry-run')) {
                    $this->line("due: {$user->email} → {$key}");

                    return;
                }

                // Mark first so a mail failure never causes duplicate sends on retry.
                DB::connection('mysql')->table('drip_emails')->insert([
                    'user_id' => $user->id,
                    'email_key' => $key,
                    'sent_at' => now(),
                ]);

                try {
                    Mail::to($user->email)->send(new DripMail(
                        key: $key,
                        name: $user->name,
                        userId: $user->id,
                        lang: in_array($user->locale, ['en', 'de'], true) ? $user->locale : 'en',
                    ));
                    $sent++;
                    $this->line("sent: {$user->email} → {$key}");
                } catch (Throwable $e) {
                    Log::warning('Drip email failed', ['user' => $user->id, 'key' => $key, 'error' => $e->getMessage()]);
                }
            });

        $this->info("Sent {$sent} drip email(s).");

        return self::SUCCESS;
    }

    /**
     * State flags for the conditional activation nudges (see
     * DripSeries::CONDITIONS). Empty for users who own no workspace (member
     * track — the nudges never apply); on failure everything defaults to
     * "already set up" so nobody gets a wrong nudge.
     *
     * @return array<string, bool>
     */
    private function ownerWorkspaceState(User $user): array
    {
        $workspace = $user->workspaces()->wherePivot('role', 'owner')->first();

        if ($workspace === null) {
            return [];
        }

        // Review pages are CENTRAL — no tenancy needed for this one.
        $state = [
            'has_active_review_page' => ReviewPage::query()
                ->where('workspace_id', $workspace->id)
                ->where('active', true)
                ->exists(),
        ];

        $previous = tenant();

        try {
            tenancy()->initialize($workspace);

            $state['has_locations'] = Location::query()->exists();
            $state['has_automations'] = Automation::query()->exists();
            $state['has_competitors'] = Competitor::query()->exists();
        } catch (Throwable $e) {
            Log::warning('Drip: workspace state check failed', ['workspace' => $workspace->id, 'error' => $e->getMessage()]);

            return [];
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }

        return $state;
    }
}
