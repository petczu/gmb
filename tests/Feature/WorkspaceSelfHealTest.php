<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\SetCurrentWorkspace;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Workspaces\WorkspaceProvisioner;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * A signed-in user with no workspace at all (registration whose provisioning
 * crashed mid-way) must get a fresh workspace from the middleware instead of
 * running the panel without a tenant. Membership and provisioning are mocked
 * so the test needs no central DB.
 */
class WorkspaceSelfHealTest extends TestCase
{
    protected function tearDown(): void
    {
        tenancy()->end();
        Mockery::close();
        parent::tearDown();
    }

    private function userWithoutWorkspaces(): User
    {
        $relation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $relation->shouldReceive('first')->andReturnNull();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('workspaces')->andReturn($relation);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);

        return $user;
    }

    public function test_user_without_any_workspace_gets_one_provisioned(): void
    {
        session()->forget('current_workspace_id');

        $user = $this->userWithoutWorkspaces();

        $workspace = new Workspace;
        $workspace->id = 'ws-healed';

        $provisioner = Mockery::mock(WorkspaceProvisioner::class);
        $provisioner->shouldReceive('create')->once()->with($user, '')->andReturn($workspace);
        $this->app->instance(WorkspaceProvisioner::class, $provisioner);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn (): User => $user);

        (new SetCurrentWorkspace)->handle($request, fn (): \Symfony\Component\HttpFoundation\Response => response('ok'));

        $this->assertSame('ws-healed', session('current_workspace_id'));
        $this->assertTrue(tenancy()->initialized);
        $this->assertSame('ws-healed', tenant('id'));
    }

    public function test_guest_request_does_not_provision_anything(): void
    {
        session()->forget('current_workspace_id');

        $provisioner = Mockery::mock(WorkspaceProvisioner::class);
        $provisioner->shouldNotReceive('create');
        $this->app->instance(WorkspaceProvisioner::class, $provisioner);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn (): ?User => null);

        (new SetCurrentWorkspace)->handle($request, fn (): \Symfony\Component\HttpFoundation\Response => response('ok'));

        $this->assertNull(session('current_workspace_id'));
        $this->assertFalse(tenancy()->initialized);
    }
}
