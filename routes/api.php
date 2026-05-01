<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Endpoint stateless untuk konsumsi third-party. Prefix `/api` otomatis
| ditangani Laravel via `bootstrap/app.php` → `withRouting(api: ...)`.
|
| Lapisan keamanan yang diterapkan (urutan middleware penting):
|
|   1. `throttle:60,1`         — rate limit 60 req/menit per token+IP.
|
|   2. `auth:sanctum`          — Bearer token (Sanctum PAT) wajib.
|                                401 Unauthorized tanpa token valid.
|
|   3. `api_client.active`     — tolak jika ApiClient (tokenable) nonaktif.
|                                Token PAT kontekstual milik user/internal
|                                di-skip (instanceof check).
|
|   4. `api_client.origin`     — jika ApiClient.enforce_origin = true,
|                                cek header `Origin` request match host
|                                yang terdaftar di kolom `url`. Skip jika
|                                enforce_origin = false.
|
|   5. `ability:...`           — scope check di token abilities Sanctum
|                                (mis. `items:read`, `item-requests:create`).
|
| Konsumer wajib kirim header:
|   Authorization: Bearer {token}
|   Accept:        application/json
|   Origin:        {url-yang-terdaftar}        ← jika enforce_origin = true
*/

use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'throttle:60,1',
    'auth:sanctum',
    'api_client.active',
    'api_client.origin',
])->group(function () {

    // ── Auth (any authenticated token holder) ────────────────────────────
    Route::post('/auth/logout', LogoutController::class)
        ->name('api.auth.logout');

    // ── Item Requests (write) ─────────────────────────────────────────────
    Route::middleware('ability:item-requests:create')
        ->post('/item-requests', [ItemRequestController::class, 'store'])
        ->name('api.item-requests.store');

    // ── Items (read-only) ─────────────────────────────────────────────────
    Route::middleware('ability:items:read')
        ->prefix('items')
        ->group(function () {
            Route::get('/', [ItemController::class, 'index'])
                ->name('api.items.index');

            // Constraint UUID 8-4-4-4-12 untuk mencegah enumerasi numerik
            // dan menolak path malformed di router level (404 lebih cepat).
            Route::get('/{uid}', [ItemController::class, 'show'])
                ->where('uid', '^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$')
                ->name('api.items.show');
        });

});
