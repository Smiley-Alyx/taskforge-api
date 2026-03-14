<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationTokenFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_accept_invitation_by_token_route(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $token = Str::random(48);

        Invitation::query()->create([
            'workspace_id' => $workspace->getKey(),
            'email' => $invitee->email,
            'role' => WorkspaceRole::Member->value,
            'token' => $token,
            'invited_by' => $owner->getKey(),
            'expires_at' => now()->addDay(),
        ]);

        $apiToken = $invitee->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->postJson("/api/v1/invitations/{$token}/accept");

        $response->assertOk();
        $response->assertJsonPath('data.workspace_id', $workspace->getKey());

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->getKey(),
            'user_id' => $invitee->getKey(),
            'role' => WorkspaceRole::Member->value,
        ]);

        $this->assertDatabaseHas('invitations', [
            'workspace_id' => $workspace->getKey(),
            'token' => $token,
        ]);
    }

    public function test_user_can_decline_invitation_by_token_route(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $token = Str::random(48);

        Invitation::query()->create([
            'workspace_id' => $workspace->getKey(),
            'email' => $invitee->email,
            'role' => WorkspaceRole::Viewer->value,
            'token' => $token,
            'invited_by' => $owner->getKey(),
            'expires_at' => now()->addDay(),
        ]);

        $apiToken = $invitee->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->postJson("/api/v1/invitations/{$token}/decline");

        $response->assertOk();
        $response->assertJsonPath('data.workspace_id', $workspace->getKey());

        $this->assertDatabaseHas('invitations', [
            'workspace_id' => $workspace->getKey(),
            'token' => $token,
        ]);

        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->getKey(),
            'user_id' => $invitee->getKey(),
        ]);
    }

    public function test_user_cannot_accept_or_decline_foreign_invitation_token(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $workspace = Workspace::query()->create([
            'owner_id' => $owner->getKey(),
            'name' => 'W',
            'slug' => 'w',
        ]);

        $realInvitee = User::factory()->create(['email' => 'invitee@example.com']);
        $attacker = User::factory()->create(['email' => 'attacker@example.com']);
        $token = Str::random(48);

        Invitation::query()->create([
            'workspace_id' => $workspace->getKey(),
            'email' => $realInvitee->email,
            'role' => WorkspaceRole::Member->value,
            'token' => $token,
            'invited_by' => $owner->getKey(),
            'expires_at' => now()->addDay(),
        ]);

        $apiToken = $attacker->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->postJson("/api/v1/invitations/{$token}/accept")
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$apiToken)
            ->postJson("/api/v1/invitations/{$token}/decline")
            ->assertForbidden();

        $this->assertDatabaseMissing('workspace_members', [
            'workspace_id' => $workspace->getKey(),
            'user_id' => $attacker->getKey(),
        ]);
    }
}
