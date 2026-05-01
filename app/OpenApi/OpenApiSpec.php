<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Kontainer metadata OpenAPI 3 untuk API publik SSB Warehouse.
 *
 * Class ini TIDAK dipakai saat runtime — hanya berfungsi sebagai tempat
 * berlabuh PHP 8 Attributes yang dipindai oleh `zircote/swagger-php`
 * (via paket `darkaonline/l5-swagger`) saat `php artisan l5-swagger:generate`
 * dijalankan. Syntax Attribute dipilih karena merupakan bentuk primer di
 * swagger-php 6.x (docblock mulai deprecated).
 *
 * Perintah generate:
 *   php artisan l5-swagger:generate
 *
 * UI tersedia di `/api/documentation`.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'SSB Warehouse API',
    description: 'API stateless untuk integrasi third-party dengan modul '
        .'manajemen gudang SSB. Semua endpoint butuh Bearer token (Personal '
        .'Access Token Sanctum) yang dibuat lewat panel admin internal. '
        .'Rate limit default 60 request/menit per kombinasi IP+token. Semua '
        .'timestamp dikirim dalam ISO 8601 UTC.',
    contact: new OA\Contact(name: 'SSB IT Integration', email: 'ict.integration@kalla.co.id'),
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'Environment aktif')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum PAT',
    description: 'Kirim header `Authorization: Bearer {token}`. Token '
        .'diterbitkan oleh admin lewat fitur Personal Access Tokens.'
)]
#[OA\Tag(name: 'Auth', description: 'Token lifecycle — revoke / refresh Personal Access Token.')]
#[OA\Tag(name: 'Items', description: 'Master barang & jasa — read-only dari sisi API publik.')]
#[OA\Tag(name: 'Item Requests', description: 'Permintaan barang (Item Request) — create dari sistem eksternal.')]
#[OA\Schema(
    schema: 'ErrorEnvelope',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized.'),
        new OA\Property(property: 'data', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'errors', type: 'object', nullable: true,
            example: ['field' => ['Validation error message.']]
        ),
    ],
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 125),
        new OA\Property(property: 'last_page', type: 'integer', example: 13),
    ],
)]
final class OpenApiSpec
{
    // Sengaja kosong — class ini hanya attribute carrier.
}
