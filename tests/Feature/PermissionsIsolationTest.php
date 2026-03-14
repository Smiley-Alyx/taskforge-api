<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_foreign_workspace_resources(): void
    {
        $ownerA = User::factory()->create();
        $workspaceA = Workspace::query()->create([
            'owner_id' => $ownerA->getKey(),
            'name' => 'A',
            'slug' => 'a',
        ]);

        $projectA = Project::query()->create([
            'workspace_id' => $workspaceA->getKey(),
            'key' => 'A',
            'name' => 'PA',
            'is_archived' => false,
            'created_by' => $ownerA->getKey(),
        ]);

        $taskA = Task::query()->create([
            'workspace_id' => $workspaceA->getKey(),
            'project_id' => $projectA->getKey(),
            'number' => 1,
            'title' => 'TA',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'reporter_id' => $ownerA->getKey(),
            'due_at' => null,
            'position' => null,
        ]);

        $userB = User::factory()->create();
        $tokenB = $userB->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson("/api/v1/workspaces/{$workspaceA->getKey()}")
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson("/api/v1/workspaces/{$workspaceA->getKey()}/projects")
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson("/api/v1/workspaces/{$workspaceA->getKey()}/projects/{$projectA->getKey()}")
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson("/api/v1/workspaces/{$workspaceA->getKey()}/tasks/{$taskA->getKey()}")
            ->assertForbidden();

        WorkspaceMember::query()->create([
            'workspace_id' => $workspaceA->getKey(),
            'user_id' => $userB->getKey(),
            'role' => WorkspaceRole::Viewer->value,
            'joined_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson("/api/v1/workspaces/{$workspaceA->getKey()}")
            ->assertOk();
    }
}
