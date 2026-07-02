<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\WorkspaceSwitchController;
use App\Models\User;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * The switch controller only writes the session pointer when the user actually
 * belongs to the target workspace. Membership is mocked so the test needs no
 * central DB.
 */
class WorkspaceSwitchTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function userWithMembership(string $workspaceId, bool $isMember): User
    {
        $relation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $relation->shouldReceive('whereKey')->with($workspaceId)->andReturnSelf();
        $relation->shouldReceive('exists')->andReturn($isMember);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('workspaces')->andReturn($relation);

        return $user;
    }

    private function switchRequest(string $workspaceId, User $user): Request
    {
        $request = Request::create('/workspace/switch', 'POST', ['workspace' => $workspaceId]);
        $request->setUserResolver(fn (): User => $user);

        return $request;
    }

    public function test_member_switch_sets_the_session_pointer(): void
    {
        session()->forget('current_workspace_id');
        $user = $this->userWithMembership('ws-1', true);

        $response = (new WorkspaceSwitchController)($this->switchRequest('ws-1', $user));

        $this->assertSame('ws-1', session('current_workspace_id'));
        $this->assertSame(url('/'), $response->getTargetUrl());
    }

    public function test_non_member_switch_is_ignored(): void
    {
        session(['current_workspace_id' => 'original']);
        $user = $this->userWithMembership('ws-2', false);

        (new WorkspaceSwitchController)($this->switchRequest('ws-2', $user));

        $this->assertSame('original', session('current_workspace_id'));
    }
}
