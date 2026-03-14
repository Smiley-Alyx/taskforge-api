<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceMemberRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_member_role(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $admin = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $admin->getKey(),
            'role' => WorkspaceRole::Admin->value,
            'joined_at' => now(),
        ]);

        $member = User::factory()->create();
        $memberRow = WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $member->getKey(),
            'role' => WorkspaceRole::Viewer->value,
            'joined_at' => now(),
        ]);

        $apiToken = $admin->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->patchJson("/api/v1/workspaces/{$workspace->getKey()}/members/{$memberRow->getKey()}", [
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.role', WorkspaceRole::Member->value);

        $this->assertDatabaseHas('workspace_members', [
            'id' => $memberRow->getKey(),
            'role' => WorkspaceRole::Member->value,
        ]);
    }

    public function test_viewer_cannot_change_member_role(): void
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

        $member = User::factory()->create();
        $memberRow = WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $member->getKey(),
            'role' => WorkspaceRole::Viewer->value,
            'joined_at' => now(),
        ]);

        $apiToken = $viewer->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->patchJson("/api/v1/workspaces/{$workspace->getKey()}/members/{$memberRow->getKey()}", [
                'role' => WorkspaceRole::Member->value,
            ]);

        $response->assertForbidden();
    }
}
