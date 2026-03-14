<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_create_update_or_delete_project(): void
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

        $project = Project::query()->create([
            'workspace_id' => $workspace->getKey(),
            'key' => 'TF',
            'name' => 'P',
            'description' => null,
            'is_archived' => false,
            'created_by' => $owner->getKey(),
        ]);

        $token = $viewer->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workspaces/{$workspace->getKey()}/projects", [
                'key' => 'NEW',
                'name' => 'New',
            ])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$project->getKey()}", [
                'name' => 'Changed',
            ])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$project->getKey()}")
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_project(): void
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

        $token = $admin->createToken('api')->plainTextToken;

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workspaces/{$workspace->getKey()}/projects", [
                'key' => 'NEW',
                'name' => 'New',
            ]);

        $create->assertCreated();
        $projectId = (int) $create->json('data.id');

        $update = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$projectId}", [
                'name' => 'Changed',
            ]);

        $update->assertOk();
        $this->assertDatabaseHas('projects', [
            'id' => $projectId,
            'name' => 'Changed',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$projectId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('projects', [
            'id' => $projectId,
        ]);
    }
}
