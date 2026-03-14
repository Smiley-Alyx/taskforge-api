<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\WorkspaceRoleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskCreatePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_task_but_viewer_cannot(): void
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

        $member = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $member->getKey(),
            'role' => WorkspaceRole::Member->value,
            'joined_at' => now(),
        ]);

        $viewer = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $viewer->getKey(),
            'role' => WorkspaceRole::Viewer->value,
            'joined_at' => now(),
        ]);

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->getKey(),
            'user_id' => $member->getKey(),
            'role' => WorkspaceRole::Member->value,
        ]);

        /** @var WorkspaceRoleResolver $resolver */
        $resolver = app(WorkspaceRoleResolver::class);
        $this->assertSame(WorkspaceRole::Member, $resolver->resolve($member, $workspace));

        $this->assertTrue($member->can('createTask', $project));
        $this->assertFalse($viewer->can('createTask', $project));

        Sanctum::actingAs($viewer);

        $payload = [
            'title' => 'TT',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'assignee_id' => null,
            'due_at' => null,
            'position' => null,
        ];

        $this->postJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$project->getKey()}/tasks", $payload)
            ->assertForbidden();

        Sanctum::actingAs($member);

        $create = $this->postJson("/api/v1/workspaces/{$workspace->getKey()}/projects/{$project->getKey()}/tasks", $payload);

        $create->assertCreated();
        $this->assertDatabaseHas('tasks', [
            'workspace_id' => $workspace->getKey(),
            'project_id' => $project->getKey(),
            'title' => 'TT',
        ]);
    }
}
