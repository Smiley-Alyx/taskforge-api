<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_attach_and_detach_labels(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $member = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $member->getKey(),
            'role' => WorkspaceRole::Member->value,
            'joined_at' => now(),
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

        $labelA = Label::query()->create([
            'workspace_id' => $workspace->getKey(),
            'name' => 'bug',
            'color' => '#ff0000',
        ]);

        $labelB = Label::query()->create([
            'workspace_id' => $workspace->getKey(),
            'name' => 'backend',
            'color' => '#00ff00',
        ]);

        $token = $member->createToken('api')->plainTextToken;

        $attach = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/{$task->getKey()}/labels", [
                'label_ids' => [$labelA->getKey(), $labelB->getKey()],
            ]);

        $attach->assertOk();
        $this->assertDatabaseHas('label_task', [
            'task_id' => $task->getKey(),
            'label_id' => $labelA->getKey(),
        ]);

        $detach = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/{$task->getKey()}/labels/{$labelA->getKey()}");

        $detach->assertNoContent();
        $this->assertDatabaseMissing('label_task', [
            'task_id' => $task->getKey(),
            'label_id' => $labelA->getKey(),
        ]);
    }
}
