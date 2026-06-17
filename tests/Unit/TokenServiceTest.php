<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_returns_plain_token_and_persists_hash(): void
    {
        $user = User::factory()->create();
        $result = app(TokenService::class)->issue($user, 'cli');

        $this->assertNotEmpty($result['token']);
        $this->assertSame(hash('sha256', $result['token']), $result['model']->token);
        $this->assertSame($user->id, $result['model']->user_id);
    }

    public function test_revoke_deletes_the_token(): void
    {
        $user = User::factory()->create();
        $svc = app(TokenService::class);
        $issued = $svc->issue($user, 'cli');

        $svc->revoke($issued['model']);

        $this->assertDatabaseMissing('api_tokens', ['id' => $issued['model']->id]);
    }
}
