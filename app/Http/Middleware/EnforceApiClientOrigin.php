<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce Origin header check untuk request dari ApiClient.
 *
 * Behavior matrix:
 *   - Tokenable BUKAN ApiClient (mis. token user internal)  → bypass.
 *   - ApiClient.enforce_origin = false                      → bypass.
 *   - ApiClient.enforce_origin = true:
 *       - Header `Origin` tidak ada                          → 403.
 *       - Host dari `Origin` tidak match host dari `url`     → 403.
 *       - Match                                              → lolos.
 *
 * Dipasang SETELAH `auth:sanctum` (butuh user() terisi) dan biasanya
 * SETELAH `api_client.active` (jangan repot cek origin kalau client
 * sudah disable).
 *
 * Comparison via `parse_url('host')` — port/scheme/path di-abaikan,
 * hanya hostname (case-insensitive) yang dibandingkan.
 */
class EnforceApiClientOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenable = $request->user();

        // Token bukan ApiClient (mis. user internal lewat) — bypass.
        if (! $tokenable instanceof ApiClient) {
            return $next($request);
        }

        // Client memilih disable Origin enforcement — bypass.
        if (! $tokenable->enforce_origin) {
            return $next($request);
        }

        $origin = $request->header('Origin');

        if (! $tokenable->originMatches($origin)) {
            return response()->json([
                'success' => false,
                'message' => 'Request Origin does not match the registered URL for this API client.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        return $next($request);
    }
}
