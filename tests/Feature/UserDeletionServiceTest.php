<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Services\Account\UserDeletionService;
use App\Services\Account\WorkspaceDeletionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Super-admin user deletion: purges workspaces the user is the sole member of,
 * detaches shared ones, cleans central rows, and refuses super admins.
 */
class UserDeletionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('mysql');

        Schema::connection('mysql')->create('users', function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Workspace = stancl tenants table, on the default (test) connection.
        Schema::create('tenants', function ($table): void {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();
        });

        // The pivot is queried from both sides: $user->workspaces() runs on the
        // related Workspace's (default) connection, $workspace->users() on the
        // related User's ('mysql') connection. In prod both are the same DB; in
        // this test they are two sqlite memories, so create the pivot on both
        // and seed it via membership().
        foreach ([Schema::connection('mysql'), Schema::connection(null)] as $schema) {
            $schema->create('workspace_user', function ($table): void {
                $table->increments('id');
                $table->string('workspace_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role')->default('member');
                $table->string('membership_type')->default('internal');
                $table->text('permissions')->nullable();
                $table->timestamps();
            });
        }

        // The service cleans these via DB::table() on the DEFAULT (central)
        // connection, so create them there — not on the reconfigured alias.
        foreach (['sessions', 'oauth_access_tokens', 'oauth_auth_codes', 'oauth_device_codes',
            'agent_conversations', 'model_has_roles', 'model_has_permissions'] as $name) {
            Schema::create($name, function ($table) use ($name): void {
                $table->increments('id');
                if (in_array($name, ['model_has_roles', 'model_has_permissions'], true)) {
                    $table->string('model_type')->nullable();
                    $table->unsignedBigInteger('model_id')->nullable();
                } else {
                    $table->unsignedBigInteger('user_id')->nullable();
                }
            });
        }
        Schema::create('agent_conversation_messages', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('conversation_id')->nullable();
        });

        // Spatie also cleans role assignments in User::delete() model events,
        // on the User model's ('mysql') connection — mirror the tables there.
        foreach (['model_has_roles', 'model_has_permissions'] as $name) {
            Schema::connection('mysql')->create($name, function ($table): void {
                $table->increments('id');
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
            });
        }
    }

    protected function tearDown(): void
    {
        foreach (['users', 'workspace_user', 'model_has_roles', 'model_has_permissions'] as $name) {
            Schema::connection('mysql')->dropIfExists($name);
        }
        Schema::dropIfExists('workspace_user');
        foreach (['sessions', 'oauth_access_tokens', 'oauth_auth_codes', 'oauth_device_codes',
            'agent_conversations', 'agent_conversation_messages',
            'model_has_roles', 'model_has_permissions'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::dropIfExists('tenants');
        parent::tearDown();
    }

    private function service(WorkspaceDeletionService $workspaces): UserDeletionService
    {
        return new UserDeletionService($workspaces);
    }

    private function workspace(string $id): Workspace
    {
        return Workspace::withoutEvents(fn () => Workspace::create(['id' => $id, 'name' => strtoupper($id)]));
    }

    private function membership(string $workspaceId, int $userId, string $role): void
    {
        foreach ([DB::connection('mysql'), DB::connection()] as $db) {
            $db->table('workspace_user')->insert([
                'workspace_id' => $workspaceId, 'user_id' => $userId, 'role' => $role,
            ]);
        }
    }

    public function test_sole_workspaces_are_purged_and_shared_ones_kept(): void
    {
        $user = User::create(['name' => 'Doomed', 'email' => 'doomed@example.com', 'password' => 'secret-secret-1']);
        $other = User::create(['name' => 'Stays', 'email' => 'stays@example.com', 'password' => 'secret-secret-1']);

        $solo = $this->workspace('ws-solo');
        $shared = $this->workspace('ws-shared');
        $this->membership('ws-solo', $user->id, 'owner');
        $this->membership('ws-shared', $user->id, 'member');
        $this->membership('ws-shared', $other->id, 'owner');

        DB::table('sessions')->insert(['user_id' => $user->id]);
        DB::table('model_has_roles')->insert(['model_type' => User::class, 'model_id' => $user->id]);
        DB::table('agent_conversations')->insert(['id' => 7, 'user_id' => $user->id]);
        DB::table('agent_conversation_messages')->insert(['conversation_id' => 7]);

        $purged = [];
        $workspaces = $this->mock(WorkspaceDeletionService::class, function ($mock) use (&$purged): void {
            $mock->shouldReceive('purge')->andReturnUsing(function (Workspace $w) use (&$purged): void {
                $purged[] = $w->id;
                Workspace::withoutEvents(fn () => $w->delete());
            });
        });

        $this->service($workspaces)->delete($user);

        $this->assertSame(['ws-solo'], $purged);
        $this->assertNull(User::find($user->id));
        $this->assertNotNull(Workspace::find('ws-shared'));
        // The service's detach runs on the default (central) connection.
        $this->assertSame(0, DB::table('workspace_user')->where('user_id', $user->id)->count());
        $this->assertSame(1, DB::table('workspace_user')->where('workspace_id', 'ws-shared')->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertSame(0, DB::table('model_has_roles')->where('model_id', $user->id)->count());
        $this->assertSame(0, DB::table('agent_conversations')->count());
        $this->assertSame(0, DB::table('agent_conversation_messages')->count());
    }

    public function test_super_admins_cannot_be_deleted(): void
    {
        config()->set('superadmin.emails', ['boss@example.com']);
        $boss = User::create(['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'secret-secret-1']);

        $this->expectException(\LogicException::class);

        $this->service(app(WorkspaceDeletionService::class))->delete($boss);
    }
}
