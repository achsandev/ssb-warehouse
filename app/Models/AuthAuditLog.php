<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uid
 * @property int|null $user_id
 * @property string|null $email
 * @property string $event
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 */
class AuthAuditLog extends Model
{
    /** Audit log immutable — tidak punya updated_at. */
    public const UPDATED_AT = null;

    protected $table = 'auth_audit_logs';

    protected $fillable = [
        'uid',
        'user_id',
        'email',
        'event',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /** Daftar event yang dikenali — referensi konstan untuk service caller. */
    public const EVENT_LOGIN_SUCCESS = 'login_success';
    public const EVENT_LOGIN_FAILED = 'login_failed';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_SESSION_EXPIRED = 'session_expired';
    public const EVENT_ACCESS_DENIED = 'access_denied';

    protected static function boot(): void
    {
        parent::boot();

        // UID UUID auto-generate — supaya log bisa direferensi tanpa expose
        // numeric ID (sama pola dengan tabel domain lain di project).
        static::creating(function (self $model): void {
            if (empty($model->uid)) {
                $model->uid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes — query sugar untuk dashboard/forensic ──────────────────

    public function scopeEvent(Builder $q, string $event): Builder
    {
        return $q->where('event', $event);
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeFromIp(Builder $q, string $ip): Builder
    {
        return $q->where('ip_address', $ip);
    }

    /** Login gagal dari IP yang sama dalam jendela waktu — deteksi brute force. */
    public function scopeRecentFailedFromIp(Builder $q, string $ip, int $minutes = 15): Builder
    {
        return $q
            ->where('event', self::EVENT_LOGIN_FAILED)
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
