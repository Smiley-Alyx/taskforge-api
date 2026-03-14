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

class WorkspaceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_workspace_project_task_and_comment(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $workspaceResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/workspaces', [
                'name' => 'My Workspace',
                'slug' => 'my-workspace',
            ]);

        $workspaceResponse->assertCreated();
        $workspaceId = $workspaceResponse->json('data.id');

        $projectResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workspaces/{$workspaceId}/projects", [
                'key' => 'TF',
                'name' => 'TaskForge',
            ]);

        $projectResponse->assertCreated();
        $projectId = $projectResponse->json('data.id');

        $taskResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workspaces/{$workspaceId}/projects/{$projectId}/tasks", [
                'title' => 'Initial task',
                'status' => 'todo',
                'priority' => 'medium',
            ]);

        $taskResponse->assertCreated();
        $taskId = $taskResponse->json('data.id');

        $commentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workspaces/{$workspaceId}/tasks/{$taskId}/comments", [
                'body' => 'First comment',
            ]);

        $commentResponse->assertCreated();

        $this->assertDatabaseHas('workspaces', ['id' => $workspaceId, 'owner_id' => $user->getKey()]);
        $this->assertDatabaseHas('projects', ['id' => $projectId, 'workspace_id' => $workspaceId]);
        $this->assertDatabaseHas('tasks', ['id' => $taskId, 'project_id' => $projectId]);
        $this->assertDatabaseHas('comments', ['task_id' => $taskId, 'author_id' => $user->getKey()]);
    }

    public function test_viewer_cannot_update_project(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $project = Project::query()->create([
            'workspace_id' => $workspace->getKey(),
            'key' => 'TF',
            'name' => 'P',
            'is_archived' => false,
            'created_by' => $owner->getKey(),
        ]);

        $viewer = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $viewer->getKey(),
            'role' => WorkspaceRole::Viewer->value,
            'joined_at' => now(),
        ]);

        $token = $viewer->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$project->getKey()}", [
                'name' => 'New',
            ]);

        $response->assertForbidden();
    }
}
