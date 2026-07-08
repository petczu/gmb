<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\DripMail;
use App\Models\Location;
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

                // The conditional connect-nudge needs the real location state,
                // but only while it could still be sent — skip the tenant
                // round-trip once it's consumed.
                $hasLocations = in_array('drip_connect', $already, true)
                    || $this->ownerWorkspaceHasLocations($user);

                $key = $series->dueStep($user, $already, hasLocations: $hasLocations);

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
     * Whether the user's owned workspace has any connected location. True for
     * users who own no workspace (member track — the nudge never applies).
     */
    private function ownerWorkspaceHasLocations(User $user): bool
    {
        $workspace = $user->workspaces()->wherePivot('role', 'owner')->first();

        if ($workspace === null) {
            return true;
        }

        $previous = tenant();

        try {
            tenancy()->initialize($workspace);

            return Location::query()->exists();
        } catch (Throwable $e) {
            Log::warning('Drip: location check failed', ['workspace' => $workspace->id, 'error' => $e->getMessage()]);

            return true;
        } finally {
            $previous !== null ? tenancy()->initialize($previous) : tenancy()->end();
        }
    }
}
