<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ApiClient\GenerateTokenRequest;
use App\Http\Resources\ApiClientTokenResource;
use App\Models\ApiClient;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Token lifecycle untuk ApiClient — generate (replace lama) & delete.
 *
 * Single-active-token model: setiap `generate` akan menghapus token lama
 * sebelum membuat yang baru. Operasi atomic via DB::transaction supaya
 * tidak ada window dimana client punya 2 token aktif (atau 0 token saat
 * insert gagal setelah delete sukses).
 */
class ApiClientTokenController extends Controller
{
    use ApiResponse;

    /**
     * Generate token baru untuk client. Token lama (jika ada) di-delete
     * lebih dulu. Plaintext token hanya dikembalikan SEKALI di response.
     */
    public function generate(GenerateTokenRequest $request, string $uid): JsonResponse
    {
        $client = ApiClient::where('uid', $uid)->firstOrFail();

        $newToken = DB::transaction(function () use ($client, $request) {
            // Hapus semua token lama (single-active model).
            $client->tokens()->delete();

            return $client->createToken(
                name: (string) $request->input('name'),
                abilities: $request->abilities(),
                expiresAt: $request->expiresAt(),
            );
        });

        return $this->successResponse(
            new ApiClientTokenResource($newToken),
            'Token generated successfully. Copy now — plaintext will not be shown again.',
            201,
        );
    }

    /**
     * Hapus (revoke) semua token milik client tanpa generate yang baru.
     * Setelah ini, client tidak bisa akses API sampai admin generate ulang.
     */
    public function destroy(string $uid): JsonResponse
    {
        $client = ApiClient::where('uid', $uid)->firstOrFail();

        $deleted = $client->tokens()->delete();

        return $this->successResponse(
            ['revoked_count' => $deleted],
            'Token deleted successfully',
        );
    }
}
