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

class TaskBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_bulk_update_tasks(): void
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
            'title' => 'T',
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

        $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/bulk", [
            'task_ids' => [$task->getKey()],
            'changes' => [
                'status' => 'done',
            ],
        ])->assertForbidden();
    }

    public function test_admin_can_bulk_update_tasks(): void
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

        $taskA = Task::query()->create([
            'workspace_id' => $workspace->getKey(),
            'project_id' => $project->getKey(),
            'number' => 1,
            'title' => 'A',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'reporter_id' => $owner->getKey(),
            'due_at' => null,
            'position' => null,
        ]);

        $taskB = Task::query()->create([
            'workspace_id' => $workspace->getKey(),
            'project_id' => $project->getKey(),
            'number' => 2,
            'title' => 'B',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'reporter_id' => $owner->getKey(),
            'due_at' => null,
            'position' => null,
        ]);

        $admin = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $admin->getKey(),
            'role' => WorkspaceRole::Admin->value,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $res = $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/bulk", [
            'task_ids' => [$taskA->getKey(), $taskB->getKey()],
            'changes' => [
                'status' => 'done',
            ],
        ]);

        $res->assertOk();
        $this->assertDatabaseHas('tasks', [
            'id' => $taskA->getKey(),
            'status' => 'done',
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $taskB->getKey(),
            'status' => 'done',
        ]);
    }
}
