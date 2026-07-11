<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pricing deep link: /register?plan=…&interval=… parks the selection in the
 * session so the onboarding wizard can preselect it after sign-up.
 */
class RegisterPlanDeepLinkTest extends TestCase
{
    public function test_plan_and_interval_are_parked_in_the_session(): void
    {
        $this->get('/register?plan=pro&interval=year')
            ->assertOk()
            ->assertSessionHas('intended_plan', 'pro')
            ->assertSessionHas('intended_interval', 'yearly');
    }

    public function test_interval_spellings_are_normalized(): void
    {
        $this->get('/register?plan=starter&interval=yearly')
            ->assertSessionHas('intended_interval', 'yearly');

        $this->get('/register?plan=starter&interval=monthly')
            ->assertSessionHas('intended_interval', 'monthly');
    }

    public function test_unknown_plan_and_interval_are_ignored(): void
    {
        $this->get('/register?plan=platinum&interval=biweekly')
            ->assertOk()
            ->assertSessionMissing('intended_plan')
            ->assertSessionMissing('intended_interval');
    }

    public function test_plain_register_page_sets_nothing(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSessionMissing('intended_plan');
    }
}
