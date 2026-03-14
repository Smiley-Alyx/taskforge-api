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

class TaskFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_date_filter_works(): void
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

        $targetDate = now()->startOfDay()->addDays(3);

        $taskIn = Task::query()->create([
            'workspace_id' => $workspace->getKey(),
            'project_id' => $project->getKey(),
            'number' => 1,
            'title' => 'In',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'reporter_id' => $owner->getKey(),
            'due_at' => $targetDate->copy()->setTime(12, 0)->toDateTimeString(),
            'position' => null,
        ]);

        Task::query()->create([
            'workspace_id' => $workspace->getKey(),
            'project_id' => $project->getKey(),
            'number' => 2,
            'title' => 'Out',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'reporter_id' => $owner->getKey(),
            'due_at' => $targetDate->copy()->addDay()->setTime(12, 0)->toDateTimeString(),
            'position' => null,
        ]);

        $token = $member->createToken('api')->plainTextToken;

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$project->getKey()}/tasks?due_date={$targetDate->toDateString()}");

        $res->assertOk();
        $res->assertJsonFragment([
            'id' => $taskIn->getKey(),
            'title' => 'In',
        ]);
        $res->assertJsonMissing([
            'title' => 'Out',
        ]);
    }
}
