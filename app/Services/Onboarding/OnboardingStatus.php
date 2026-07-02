<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Models\Location;
use App\Models\Workspace;
use App\Services\Billing\LocationBilling;

/**
 * First-run onboarding checklist for a brand-new workspace. Each step's "done"
 * state is derived from real data (no separate flags to drift), so progress is
 * picked up automatically as the user completes the existing pages/flows:
 * Company details, plan selection (Stripe), and connecting the first location.
 */
class OnboardingStatus
{
    public function __construct(private readonly LocationBilling $billing) {}

    /**
     * @return array<int, array{key: string, label: string, hint: string, done: bool, url: string}>
     */
    public function steps(Workspace $workspace): array
    {
        return [
            [
                'key' => 'company',
                'label' => __('onboarding.step_company_label'),
                'hint' => __('onboarding.step_company_hint'),
                'done' => filled($workspace->billing_country),
                'url' => '/onboarding',
            ],
            [
                'key' => 'plan',
                'label' => __('onboarding.step_plan_label'),
                'hint' => __('onboarding.step_plan_hint'),
                'done' => $this->planDone($workspace),
                'url' => '/onboarding',
            ],
            [
                'key' => 'location',
                'label' => __('onboarding.step_location_label'),
                'hint' => __('onboarding.step_location_hint'),
                'done' => once(fn () => Location::query()->exists()),
                'url' => '/onboarding',
            ],
        ];
    }

    /** All gating steps satisfied. */
    public function complete(Workspace $workspace): bool
    {
        foreach ($this->steps($workspace) as $step) {
            if (! $step['done']) {
                return false;
            }
        }

        return true;
    }

    /** The first step the user still needs to do (for the primary CTA). */
    public function nextStep(Workspace $workspace): ?array
    {
        foreach ($this->steps($workspace) as $step) {
            if (! $step['done']) {
                return $step;
            }
        }

        return null;
    }

    private function planDone(Workspace $workspace): bool
    {
        // When billing isn't configured (local/dev) this step isn't applicable.
        return ! $this->billing->enabled() || $this->billing->subscription($workspace) !== null;
    }
}
