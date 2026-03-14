<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_create_comment(): void
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

        $this->postJson("/api/v1/workspaces/{$workspace->getKey()}/tasks/{$task->getKey()}/comments", [
            'body' => 'Hello',
        ])->assertForbidden();
    }

    public function test_member_can_update_own_comment_but_not_others_member_comment(): void
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

        $author = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $author->getKey(),
            'role' => WorkspaceRole::Member->value,
            'joined_at' => now(),
        ]);

        $otherMember = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $otherMember->getKey(),
            'role' => WorkspaceRole::Member->value,
            'joined_at' => now(),
        ]);

        $comment = Comment::query()->create([
            'workspace_id' => $workspace->getKey(),
            'task_id' => $task->getKey(),
            'author_id' => $author->getKey(),
            'body' => 'Original',
            'edited_at' => null,
        ]);

        Sanctum::actingAs($otherMember);

        $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/comments/{$comment->getKey()}", [
            'body' => 'Hacked',
        ])->assertForbidden();

        Sanctum::actingAs($author);

        $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/comments/{$comment->getKey()}", [
            'body' => 'Updated',
        ])->assertOk();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'body' => 'Updated',
        ]);
    }

    public function test_admin_can_delete_other_users_comment(): void
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

        $author = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $author->getKey(),
            'role' => WorkspaceRole::Member->value,
            'joined_at' => now(),
        ]);

        $admin = User::factory()->create();
        WorkspaceMember::query()->create([
            'workspace_id' => $workspace->getKey(),
            'user_id' => $admin->getKey(),
            'role' => WorkspaceRole::Admin->value,
            'joined_at' => now(),
        ]);

        $comment = Comment::query()->create([
            'workspace_id' => $workspace->getKey(),
            'task_id' => $task->getKey(),
            'author_id' => $author->getKey(),
            'body' => 'Original',
            'edited_at' => null,
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/comments/{$comment->getKey()}")
            ->assertNoContent();

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->getKey(),
        ]);
    }
}
