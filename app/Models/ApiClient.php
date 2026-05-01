<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Aplikasi external (third-party) yang berhak mengakses `/api/*`.
 *
 * Menjadi `tokenable` untuk Sanctum Personal Access Token — token yang
 * di-issue ke client ini akan punya `tokenable_type = App\Models\ApiClient`
 * di tabel `personal_access_tokens`.
 *
 * Field utama:
 *   - `name`            : label internal (mis. "Mobile Operator", "Partner X")
 *   - `url`             : URL aplikasi konsumen — referensi & enforcement
 *                         host untuk Origin check
 *   - `enforce_origin`  : kalau true, request HARUS punya header `Origin`
 *                         yang match host `url`. Kalau false, token cukup.
 *   - `is_active`       : kill switch instan tanpa hapus row (preserve audit)
 *
 * @property int $id
 * @property string $uid
 * @property string $name
 * @property string $url
 * @property string|null $description
 * @property bool $is_active
 * @property bool $enforce_origin
 * @property int|null $created_by_id
 * @property string|null $created_by_name
 * @property int|null $updated_by_id
 * @property string|null $updated_by_name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PersonalAccessToken> $tokens
 */
class ApiClient extends Model
{
    use HasApiTokens;

    protected $table = 'api_clients';

    protected $fillable = [
        'uid',
        'name',
        'url',
        'description',
        'is_active',
        'enforce_origin',
        'created_by_id',
        'created_by_name',
        'updated_by_id',
        'updated_by_name',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'enforce_origin' => 'boolean',
    ];

    /** Internal IDs disembunyikan dari serialization default. */
    protected $hidden = [
        'id',
        'created_by_id',
        'updated_by_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    /**
     * Token aktif (paling baru) untuk client ini. Single-active-token model:
     * setiap generate ulang akan menghapus token lama, jadi list tokens
     * normalnya berisi 0 atau 1 row.
     */
    public function activeToken(): ?PersonalAccessToken
    {
        /** @var PersonalAccessToken|null */
        return $this->tokens()->latest('id')->first();
    }

    /** Apakah token client sudah expired (berdasarkan `expires_at`)? */
    public function hasExpiredToken(): bool
    {
        $token = $this->activeToken();
        if (! $token || $token->expires_at === null) {
            return false;
        }

        return $token->expires_at->isPast();
    }

    /**
     * Cek apakah host dari `Origin` request match dengan host dari `url`.
     * Comparison via `parse_url('host')` — port di-abaikan, scheme di-abaikan,
     * path di-abaikan. Hanya hostname yang dibandingkan (case-insensitive).
     */
    public function originMatches(?string $origin): bool
    {
        if ($origin === null || $origin === '') {
            return false;
        }

        $clientHost = parse_url($this->url, PHP_URL_HOST);
        $originHost = parse_url($origin, PHP_URL_HOST);

        if ($clientHost === null || $originHost === null) {
            return false;
        }

        return strcasecmp($clientHost, $originHost) === 0;
    }
}
