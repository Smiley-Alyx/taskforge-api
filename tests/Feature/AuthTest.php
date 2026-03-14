<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alyx',
            'email' => 'alyx@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'token',
            ],
        ]);
    }

    public function test_user_can_login_and_call_me_endpoint(): void
    {
        $user = User::factory()->create([
            'email' => 'alyx@example.com',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'alyx@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk();
        $token = $login->json('data.token');
        $this->assertIsString($token);

        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $me->assertOk();
        $me->assertJsonPath('data.user.id', $user->getKey());
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $logout = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logout->assertOk();

        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $me->assertUnauthorized();
    }
}
