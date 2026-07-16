<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReportSchedule;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Report-schedule recipient resolution: the new {include, exclude, emails}
 * shape (role/member selection minus exclusions, plus external emails), the
 * legacy flat email list, and the all-members fallback. Workspace membership is
 * mocked so no central DB is needed (mirrors NotificationRecipientsTest).
 */
class ReportRecipientsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return Collection<int, object> */
    private function members(): Collection
    {
        return collect([
            (object) ['id' => 1, 'email' => 'owner@x.test', 'pivot' => (object) ['role' => 'owner']],
            (object) ['id' => 2, 'email' => 'ann@x.test', 'pivot' => (object) ['role' => 'member']],
            (object) ['id' => 3, 'email' => 'bob@x.test', 'pivot' => (object) ['role' => 'member']],
        ]);
    }

    /**
     * A ReportSchedule whose membership seam returns the given members, so
     * recipient resolution runs without the central membership tables.
     */
    private function schedule(mixed $recipients, ?Collection $members = null): ReportSchedule
    {
        $schedule = new class extends ReportSchedule
        {
            public Collection $stubMembers;

            protected function workspaceMembers(Workspace $workspace): Collection
            {
                return $this->stubMembers;
            }
        };
        $schedule->stubMembers = $members ?? collect();

        return $schedule->forceFill(['recipients' => $recipients]);
    }

    public function test_role_selection_minus_exclusion_resolves_to_emails(): void
    {
        $emails = $this->schedule([
            'include' => ['role:member'],
            'exclude' => [3],
            'emails' => ['extra@x.test'],
        ], $this->members())->resolveRecipients(new Workspace);

        sort($emails);
        $this->assertSame(['ann@x.test', 'extra@x.test'], $emails);
    }

    public function test_external_emails_only(): void
    {
        // include empty → no member lookup, just the extra emails.
        $emails = $this->schedule(['include' => [], 'exclude' => [], 'emails' => ['a@x.test']])
            ->resolveRecipients(new Workspace);

        $this->assertSame(['a@x.test'], $emails);
    }

    public function test_legacy_flat_email_list_still_works(): void
    {
        $emails = $this->schedule(['old@x.test', 'two@x.test'])->resolveRecipients(new Workspace);

        $this->assertSame(['old@x.test', 'two@x.test'], $emails);
    }

    public function test_empty_selection_falls_back_to_all_members(): void
    {
        $emails = $this->schedule(['include' => [], 'exclude' => [], 'emails' => []], $this->members())
            ->resolveRecipients(new Workspace);

        $this->assertSame(['owner@x.test', 'ann@x.test', 'bob@x.test'], $emails);
    }
}
