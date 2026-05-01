<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/**
 * Resource khusus untuk response generate token — satu-satunya tempat
 * dimana plaintext token diekspos ke admin UI.
 *
 * Plaintext hanya tersedia sekali (saat issuance). Setelah response ini
 * dikirim, hash di DB tidak bisa di-reverse — admin harus copy & simpan
 * di tempat aman SEKARANG (kemudian kirim ke partner).
 *
 * Resource ini WAJIB dipanggil dengan instance `Laravel\Sanctum\NewAccessToken`
 * (return value dari `$client->createToken(...)`).
 */
class ApiClientTokenResource extends JsonResource
{
    public function __construct(NewAccessToken $token)
    {
        parent::__construct($token);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var NewAccessToken $newToken */
        $newToken = $this->resource;
        $token = $newToken->accessToken;

        return [
            // ⚠️ PLAINTEXT — hanya muncul sekali di response ini.
            'plain_text_token' => $newToken->plainTextToken,

            // Metadata untuk display di UI.
            'name'       => $token->name,
            'abilities'  => $token->abilities ?? [],
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'created_at' => optional($token->created_at)->toIso8601String(),
        ];
    }
}
