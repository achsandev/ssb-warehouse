<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Milon\Barcode\DNS2D;

class ItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Label yang ditampilkan di bawah QR untuk referensi cepat (copy/printed).
        $barcodeValue = $this->code ?: ($this->part_number ?: $this->uid);

        // QR encode URL detail item — saat discan di HP, browser akan membuka
        // aplikasi ke halaman Items dengan query `view={uid}` dan dialog detail
        // terbuka otomatis.
        $barcode = null;
        if (! empty($this->uid)) {
            $baseUrl   = rtrim((string) config('app.url'), '/');
            $detailUrl = $baseUrl . '/master/items_services?view=' . $this->uid;
            $barcode   = 'data:image/svg+xml;base64,' . base64_encode(
                (new DNS2D)->getBarcodeSVG($detailUrl, 'QRCODE', 4, 4)
            );
        }

        return [
            'uid' => $this->uid,
            'code' => $this->code,
            'barcode_value' => $barcodeValue,
            'barcode' => $barcode,
            'name' => $this->name,
            'brand' => $this->whenLoaded('brand', function () {
                return [
                    'uid' => $this->brand->uid,
                    'name' => $this->brand->name,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'uid' => $this->category->uid,
                    'name' => $this->category->name,
                ];
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'uid' => $this->unit->uid,
                    'symbol' => $this->unit->symbol,
                    'name' => $this->unit->name,
                ];
            }),
            'min_qty' => $this->min_qty,
            'part_number' => $this->part_number,
            'interchange_part' => $this->interchange_part,
            // Relative URL agar terhindar dari isu CORS (localhost vs 127.0.0.1) — resolve ke origin saat ini.
            'image' => $this->image ? '/storage/'.ltrim($this->image, '/') : null,
            'image_path' => $this->image,
            'movement_category' => $this->whenLoaded('movement_category', function () {
                return [
                    'uid' => $this->movement_category->uid,
                    'name' => $this->movement_category->name,
                ];
            }),
            'valuation_method' => $this->whenLoaded('valuation_method', function () {
                return [
                    'uid' => $this->valuation_method->uid,
                    'name' => $this->valuation_method->name,
                ];
            }),
            'material_group' => $this->whenLoaded('material_group', function () {
                return [
                    'uid' => $this->material_group->uid,
                    'code' => $this->material_group->code,
                    'name' => $this->material_group->name,
                ];
            }),
            'sub_material_group' => $this->whenLoaded('sub_material_group', function () {
                return [
                    'uid' => $this->sub_material_group->uid,
                    'code' => $this->sub_material_group->code,
                    'name' => $this->sub_material_group->name,
                ];
            }),
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'uid' => $this->supplier->uid,
                    'name' => $this->supplier->name,
                ];
            }),
            'request_types' => $this->whenLoaded('request_types', function () {
                return $this->request_types->map(function ($type) {
                    return [
                        'uid' => $type->uid,
                        'name' => $type->name,
                    ];
                });
            }),
            'unit_types' => $this->whenLoaded('item_unit_type', function () {
                return $this->item_unit_type->map(function ($type) {
                    return [
                        'uid' => $type->usage_unit?->uid,
                        'name' => $type->usage_unit?->name,
                    ];
                });
            }),
            'price' => $this->price,
            'exp_date' => $this->exp_date,
            'additional_info' => $this->additional_info,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
