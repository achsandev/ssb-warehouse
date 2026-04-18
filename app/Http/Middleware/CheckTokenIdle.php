<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenIdle
{
    /**
     * Maximum idle duration in minutes (no activity = session expired).
     */
    protected int $idleMinutes = 120;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        // Hanya berlaku untuk PersonalAccessToken (bearer token), bukan TransientToken (session).
        if (! $token instanceof PersonalAccessToken) {
            return $next($request);
        }

        $lastUsed = $token->last_used_at;

        // Jika sudah ada aktivitas sebelumnya dan idle > batas maksimal, expire token.
        if ($lastUsed && $lastUsed->lt(now()->subMinutes($this->idleMinutes))) {
            $token->delete();

            return response()->json([
                'message' => 'Session expired due to inactivity',
                'code' => 'SESSION_EXPIRED',
            ], 401);
        }

        return $next($request);
    }
}
