<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Representasi ApiClient untuk admin UI internal.
 *
 * Berbeda dengan resource API publik — di sini admin butuh metadata token
 * (kapan terakhir dipakai, kapan expire) untuk monitoring & rotation.
 *
 * Token plaintext TIDAK pernah masuk resource ini. Plaintext hanya muncul
 * sekali via `ApiClientTokenResource` saat baru di-generate.
 */
class ApiClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $token = $this->resource instanceof \App\Models\ApiClient
            ? $this->resource->activeToken()
            : null;

        return [
            'uid'            => (string) $this->uid,
            'name'           => (string) $this->name,
            'url'            => (string) $this->url,
            'description'    => $this->description,
            'is_active'      => (bool) $this->is_active,
            'enforce_origin' => (bool) $this->enforce_origin,

            'token' => $token !== null
                ? $this->serializeTokenMeta($token)
                : null,

            'created_at'      => optional($this->created_at)->toIso8601String(),
            'updated_at'      => optional($this->updated_at)->toIso8601String(),
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }

    /**
     * Metadata token — tidak ada plaintext, hanya lifecycle info.
     *
     * @return array<string, mixed>
     */
    private function serializeTokenMeta(PersonalAccessToken $token): array
    {
        return [
            'name'         => $token->name,
            'abilities'    => $token->abilities ?? [],
            'last_used_at' => optional($token->last_used_at)->toIso8601String(),
            'expires_at'   => optional($token->expires_at)->toIso8601String(),
            'is_expired'   => $token->expires_at !== null && $token->expires_at->isPast(),
            'created_at'   => optional($token->created_at)->toIso8601String(),
        ];
    }
}
