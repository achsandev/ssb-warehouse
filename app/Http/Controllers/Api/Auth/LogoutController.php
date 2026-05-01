<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

/**
 * Single-action controller: revoke Personal Access Token Sanctum.
 *
 * Dua scenario yang didukung:
 *   - Bearer token (PAT) → token di-delete dari tabel
 *     `personal_access_tokens` sehingga request berikutnya dengan token yang
 *     sama akan ditolak (401).
 *   - Session cookie (SPA stateful) → `currentAccessToken()` mengembalikan
 *     `TransientToken` yang tidak punya `delete()` yang meaningful;
 *     controller skip revoke. SPA cukup pakai endpoint logout web biasa
 *     untuk destroy session.
 */
class LogoutController extends Controller
{
    use ApiResponse;

    #[OA\Post(
        path: '/api/auth/logout',
        operationId: 'apiLogout',
        summary: 'Revoke current Personal Access Token',
        description: 'Menghapus token Sanctum yang sedang dipakai. Endpoint ini '
            .'idempotent — panggilan berulang dengan token yang sama tetap '
            .'mengembalikan 200, meski token sudah tidak valid setelah '
            .'panggilan pertama.',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logout berhasil', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil'),
                    new OA\Property(property: 'data', type: 'string', nullable: true, example: null),
                    new OA\Property(property: 'errors', type: 'string', nullable: true, example: null),
                ],
            )),
            new OA\Response(response: 401, description: 'Unauthorized — token tidak valid / sudah revoked', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 429, description: 'Too Many Requests', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        // Hanya PAT (Eloquent model) yang punya `delete()` yang benar-benar
        // revoke token secara persisten. `instanceof` check menghindari
        // TypeError bila controller dipanggil via session auth.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $this->successResponse(null, 'Logout berhasil');
    }
}
