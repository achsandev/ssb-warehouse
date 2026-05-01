<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Representasi `Items` untuk konsumen API publik.
 *
 * Berbeda dengan `ItemsResource` internal yang dipakai SPA:
 *  - Tidak membawa kolom internal (`id`, `*_id`, `*_by_id`).
 *  - Tidak men-generate barcode SVG (mahal & tidak relevan untuk API).
 *  - URL gambar dipaksa absolut dengan base host `config('app.url')` supaya
 *    aman dikonsumsi dari domain lain.
 *  - Tanggal dalam ISO 8601 UTC untuk kompatibilitas cross-timezone.
 */
#[OA\Schema(
    schema: 'Item',
    type: 'object',
    required: ['uid', 'code', 'name'],
    properties: [
        new OA\Property(property: 'uid', type: 'string', format: 'uuid', example: '7c8b0a0e-7f3f-4e2e-9b9a-1a1a1a1a1a1a'),
        new OA\Property(property: 'code', type: 'string', example: 'OIL-001-0001'),
        new OA\Property(property: 'name', type: 'string', example: 'Oli Mesin SAE 10W-40'),
        new OA\Property(property: 'part_number', type: 'string', nullable: true, example: 'SAE-10W40-A'),
        new OA\Property(property: 'interchange_part', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'min_qty', type: 'integer', example: 10),
        new OA\Property(property: 'stock', type: 'number', format: 'float', description: 'Total stock saat ini = SUM(qty) seluruh row wh_stocks untuk item ini (lintas warehouse/rack/tank). Satuan mengikuti `unit.symbol`.', example: 125.5),
        new OA\Property(property: 'price', type: 'string', nullable: true, example: '150000'),
        new OA\Property(property: 'image', type: 'string', format: 'uri', nullable: true, example: 'https://warehouse.ssb.co.id/storage/items/xyz.png'),
        new OA\Property(property: 'brand', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'category', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'unit', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'uid', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'symbol', type: 'string'),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uid'              => (string) $this->uid,
            'code'             => (string) $this->code,
            'name'             => (string) $this->name,
            'part_number'      => $this->part_number,
            'interchange_part' => $this->interchange_part,
            'min_qty'          => (int) ($this->min_qty ?? 0),
            // `total_stock` di-hydrate via withSum() di ItemController::baseQuery().
            // Null saat item tidak punya row stock sama sekali → normalisasi ke 0.
            'stock'            => (float) ($this->total_stock ?? 0),
            'price'            => $this->price,
            'image'            => $this->resolveImageUrl(),

            'brand' => $this->whenLoaded('brand', fn () => [
                'uid'  => (string) $this->brand->uid,
                'name' => (string) $this->brand->name,
            ]),

            'category' => $this->whenLoaded('category', fn () => [
                'uid'  => (string) $this->category->uid,
                'name' => (string) $this->category->name,
            ]),

            'unit' => $this->whenLoaded('unit', fn () => [
                'uid'    => (string) $this->unit->uid,
                'name'   => (string) $this->unit->name,
                'symbol' => (string) $this->unit->symbol,
            ]),

            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }

    /**
     * Pastikan URL gambar absolut (berguna saat API dipakai cross-domain).
     * Kalau `image` kosong → null; kalau sudah absolute (http/https) →
     * diteruskan apa adanya; kalau relative → di-prefix dengan `app.url`.
     */
    private function resolveImageUrl(): ?string
    {
        $path = $this->image;
        if (! $path) {
            return null;
        }

        $normalized = ltrim((string) $path, '/');

        if (preg_match('#^https?://#i', $normalized)) {
            return $normalized;
        }

        $base = rtrim((string) config('app.url'), '/');
        return $base.'/storage/'.$normalized;
    }
}
