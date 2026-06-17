<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'     => 'Alice',
            'email'    => 'alice@example.com',
            'password' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_rejects_invalid_password(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_protected_route_requires_bearer_token(): void
    {
        $this->getJson('/api/languages')->assertStatus(401);
    }

    public function test_protected_route_works_with_valid_token(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English']);
        $user = User::factory()->create();
        $token = app(TokenService::class)->issue($user, 'test')['token'];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/languages')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'code', 'name']]]);
    }

    public function test_logout_remove_token(): void
    {
        $user = User::factory()->create();
        $token = app(TokenService::class)->issue($user, 'test')['token'];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/languages')
            ->assertStatus(401);
    }
}
