<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Filament\Panel;
use Mockery;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['superadmin.emails' => ['boss@example.test']]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function panel(string $id): Panel
    {
        $panel = Mockery::mock(Panel::class);
        $panel->shouldReceive('getId')->andReturn($id);

        return $panel;
    }

    public function test_allowlisted_email_is_super_admin(): void
    {
        $this->assertTrue((new User(['email' => 'boss@example.test']))->isSuperAdmin());
        $this->assertTrue((new User(['email' => 'BOSS@EXAMPLE.TEST']))->isSuperAdmin());
    }

    public function test_other_emails_are_not_super_admin(): void
    {
        $this->assertFalse((new User(['email' => 'someone@example.test']))->isSuperAdmin());
    }

    public function test_admin_panel_is_super_admin_only(): void
    {
        $admin = $this->panel('admin');

        $this->assertTrue((new User(['email' => 'boss@example.test']))->canAccessPanel($admin));
        $this->assertFalse((new User(['email' => 'someone@example.test']))->canAccessPanel($admin));
    }

    public function test_app_panel_is_open_to_everyone(): void
    {
        $app = $this->panel('app');

        $this->assertTrue((new User(['email' => 'someone@example.test']))->canAccessPanel($app));
    }
}
