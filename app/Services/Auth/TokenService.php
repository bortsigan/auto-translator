<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Str;

class TokenService
{
    public function issue(User $user, string $name): array
    {
        $plain = Str::random(64);

        $model = ApiToken::query()->create([
            'user_id' => $user->id,
            'name'    => $name,
            'token'   => hash('sha256', $plain),
        ]);

        return ['token' => $plain, 'model' => $model];
    }

    public function revoke(ApiToken $token): void
    {
        $token->delete();
    }
}
