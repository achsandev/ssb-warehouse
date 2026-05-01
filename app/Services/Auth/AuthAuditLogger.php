<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AuthAuditLog;
use Illuminate\Http\Request;
use Throwable;

/**
 * Service untuk mencatat event auth ke `auth_audit_logs`.
 *
 * Service-based (bukan static helper) supaya bisa di-mock di tes & ditelaah
 * dependency-nya. Semua method `safe` — exception logging dibungkus
 * try/catch karena gagal tulis audit log TIDAK boleh menggagalkan response
 * utama (mis. login sukses tapi DB audit lambat — user tetap dapat 200).
 *
 * Anti-PII reminder: jangan simpan password / token raw di metadata.
 */
class AuthAuditLogger
{
    /**
     * Catat event auth.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        string $event,
        Request $request,
        ?int $userId = null,
        ?string $email = null,
        ?array $metadata = null,
    ): void {
        try {
            AuthAuditLog::create([
                'user_id'    => $userId,
                'email'      => $email !== null ? mb_substr($email, 0, 191) : null,
                'event'      => $event,
                'ip_address' => $request->ip(),
                // Cap user-agent length di 1KB untuk hindari log inflation
                // dari botnet yang kirim header sengaja besar.
                'user_agent' => $this->truncate($request->userAgent(), 1024),
                'metadata'   => $metadata,
            ]);
        } catch (Throwable $e) {
            // Audit log adalah BEST-EFFORT. Jangan throw ke caller.
            // Log ke channel default Laravel sebagai fallback observability.
            report($e);
        }
    }

    public function loginSuccess(Request $request, int $userId, string $email): void
    {
        $this->log(AuthAuditLog::EVENT_LOGIN_SUCCESS, $request, $userId, $email);
    }

    public function loginFailed(Request $request, string $email, string $reason = 'invalid_credentials'): void
    {
        $this->log(
            AuthAuditLog::EVENT_LOGIN_FAILED,
            $request,
            null,
            $email,
            ['reason' => $reason],
        );
    }

    public function logout(Request $request, int $userId, string $email): void
    {
        $this->log(AuthAuditLog::EVENT_LOGOUT, $request, $userId, $email);
    }

    public function sessionExpired(Request $request, ?int $userId = null, ?string $email = null): void
    {
        $this->log(AuthAuditLog::EVENT_SESSION_EXPIRED, $request, $userId, $email);
    }

    public function accessDenied(Request $request, int $userId, string $email, string $route): void
    {
        $this->log(
            AuthAuditLog::EVENT_ACCESS_DENIED,
            $request,
            $userId,
            $email,
            ['route' => $route],
        );
    }

    private function truncate(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $maxLength
            ? mb_substr($value, 0, $maxLength)
            : $value;
    }
}
