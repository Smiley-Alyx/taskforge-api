<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Label;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LabelPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_create_update_or_delete_label(): void
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

        $label = Label::query()->create([
            'workspace_id' => $workspace->getKey(),
            'name' => 'bug',
            'color' => '#ff0000',
        ]);

        Sanctum::actingAs($viewer);

        $this->postJson("/api/v1/workspaces/{$workspace->getKey()}/labels", [
            'name' => 'new',
            'color' => '#00ff00',
        ])->assertForbidden();

        $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/labels/{$label->getKey()}", [
            'name' => 'renamed',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/labels/{$label->getKey()}")
            ->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_label(): void
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

        Sanctum::actingAs($admin);

        $create = $this->postJson("/api/v1/workspaces/{$workspace->getKey()}/labels", [
            'name' => 'bug',
            'color' => '#ff0000',
        ]);

        $create->assertCreated();
        $labelId = (int) $create->json('data.id');

        $this->patchJson("/api/v1/workspaces/{$workspace->getKey()}/labels/{$labelId}", [
            'name' => 'backend',
        ])->assertOk();

        $this->assertDatabaseHas('labels', [
            'id' => $labelId,
            'name' => 'backend',
        ]);

        $this->deleteJson("/api/v1/workspaces/{$workspace->getKey()}/labels/{$labelId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('labels', [
            'id' => $labelId,
        ]);
    }
}
