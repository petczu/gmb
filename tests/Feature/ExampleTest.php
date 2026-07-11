<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The app panel is mounted at "/", so guests are redirected to the login
     * page rather than seeing a public landing.
     */
    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
        $this->assertStringContainsString('/login', $response->headers->get('Location', ''));
    }
}
