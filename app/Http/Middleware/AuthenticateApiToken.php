<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (empty($bearer)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hash = hash('sha256', $bearer);

        $token = ApiToken::query()->where('token', $hash)->first();

        if (empty($token)) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return response()->json(['message' => 'Token expired.'], 401);
        }

        $token->forceFill(['last_used_at' => Carbon::now()])->saveQuietly();

        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
