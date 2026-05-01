<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\AuthAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Auth controller untuk SPA stateful (cookie session).
 *
 * Pola yang dipakai:
 *   - SPA (browser) → Sanctum stateful + HttpOnly session cookie.
 *     `signin` membuat session via `Auth::attempt`, frontend tidak menerima
 *     atau menyimpan token apapun. Sliding session lifetime di-handle
 *     `SESSION_LIFETIME` (30 menit aktif sliding).
 *   - Third-party (`/api/*`) → Sanctum Personal Access Token (Bearer).
 *     Login third-party tidak via controller ini — token diterbitkan oleh
 *     admin via fitur Personal Access Tokens.
 *
 * Semua event auth (success/failed/logout) dicatat ke `auth_audit_logs`
 * lewat `AuthAuditLogger` untuk forensic & brute-force detection.
 */
class AuthController extends Controller
{
    public function __construct(private AuthAuditLogger $auditLogger)
    {
    }

    public function signup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user'    => $user,
        ], 201);
    }

    /**
     * Login SPA. Membuat session HttpOnly cookie via Sanctum stateful.
     *
     * `Auth::attempt` otomatis melakukan session ID regeneration (anti
     * session fixation). Tidak ada token yang dikembalikan ke frontend —
     * cookie di-set otomatis oleh Sanctum.
     */
    public function signin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: false)) {
            $this->auditLogger->loginFailed($request, $credentials['email']);

            // ValidationException supaya shape error konsisten dengan endpoint
            // lain (status 422 + struktur `errors`). Tidak bocorkan apakah
            // email-nya yang salah atau password-nya — pesan generik.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Eksplisit regenerate untuk berlapis (Auth::attempt sudah, tapi
        // call ini idempotent dan jadi defense-in-depth).
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        $this->auditLogger->loginSuccess($request, $user->id, $user->email);

        return response()->json([
            'message' => 'Successfully signed in',
            // Tidak return token — frontend pakai cookie session.
        ]);
    }

    /**
     * Logout SPA — invalidate session di server + regenerate CSRF token.
     */
    public function signout(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user?->id;
        $email = $user?->email;

        Auth::guard('web')->logout();

        // Invalidate session row di DB sehingga cookie lama tidak bisa dipakai
        // bahkan kalau attacker punya copy cookie-nya.
        $request->session()->invalidate();

        // Regenerate CSRF token supaya request berikutnya pakai token baru.
        $request->session()->regenerateToken();

        if ($userId && $email) {
            $this->auditLogger->logout($request, $userId, $email);
        }

        return response()->json([
            'message' => 'Successfully signed out',
        ]);
    }

    /**
     * Mengembalikan profil user yang sedang login + permission untuk CASL FE.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $abilities = $user->getAllPermissions()->map(function ($permission) {
            [$subject, $object] = explode('.', $permission->name);

            return [
                'subject' => $subject,
                'action'  => $object,
            ];
        });

        $role = $user->roles()->first();
        $roleId = $role?->id;
        $roleUid = $role?->uid;
        $departmentName = $user->userDetails()->first()?->department;

        return response()->json([
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'department_name' => $departmentName,
            'role_id'         => $roleId,
            'role_uid'        => $roleUid,
            'permissions'     => $abilities,
        ]);
    }
}
