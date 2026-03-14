<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskUpdateDeletePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_update_or_delete_task(): void
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

        $task = Task::query()->create([
            'workspace_id' => $workspace->getKey(),
            'project_id' => $project->getKey(),
            'number' => 1,
            'title' => 'TT',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'reporter_id' => $owner->getKey(),
            'due_at' => null,
            'position' => null,
        ]);

        $viewer = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $viewer->getKey(),
            'role' => WorkspaceRole::Viewer->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/{$task->getKey()}", [
            'title' => 'Updated',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/{$task->getKey()}")
            ->assertForbidden();
    }

    public function test_member_can_update_and_delete_task(): void
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

        $task = Task::query()->create([
            'workspace_id' => $workspace->getKey(),
            'project_id' => $project->getKey(),
            'number' => 1,
            'title' => 'TT',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'reporter_id' => $owner->getKey(),
            'due_at' => null,
            'position' => null,
        ]);

        $member = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $member->getKey(),
            'role' => WorkspaceRole::Member->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($member);

        $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/{$task->getKey()}", [
            'title' => 'Updated',
        ])->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'title' => 'Updated',
        ]);

        $this->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/{$task->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->getKey(),
        ]);
    }
}
