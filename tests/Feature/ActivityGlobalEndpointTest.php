<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityGlobalEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_activity_by_workspace_id_if_has_access(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $viewer = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $viewer->getKey(),
            'role' => WorkspaceRole::Viewer->value,
            'joined_at' => now(),
        ]);

        $log = ActivityLog::query()->create([
            'workspace_id' => $workspace->getKey(),
            'actor_id' => $owner->getKey(),
            'action' => 'workspace.created',
            'subject_type' => Workspace::class,
            'subject_id' => $workspace->getKey(),
            'context' => null,
            'ip' => '127.0.0.1',
            'user_agent' => 'tests',
            'created_at' => now(),
        ]);

        $token = $viewer->createToken('api')->plainTextToken;

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/activity?workspace_id='.$workspace->getKey());

        $res->assertOk();
        $res->assertJsonFragment([
            'id' => $log->getKey(),
            'workspace_id' => $workspace->getKey(),
            'action' => 'workspace.created',
        ]);
    }

    public function test_user_cannot_list_activity_for_foreign_workspace(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/activity?workspace_id='.$workspace->getKey())
            ->assertForbidden();
    }
}
