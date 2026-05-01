<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak request dari ApiClient yang di-nonaktifkan (is_active=false).
 *
 * Dipakai setelah `auth:sanctum` — butuh user() terisi.
 * Token dengan tokenable selain ApiClient di-bypass (mis. token user
 * internal lewat). Bisa dibuat strict dengan mengubah `tokenable instanceof`
 * menjadi hard-require.
 */
class EnforceApiClientActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenable = $request->user();

        if ($tokenable instanceof ApiClient && ! $tokenable->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'API client is disabled.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
