<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Milon\Barcode\DNS2D;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $d = new DNS2D;
        $barcode = 'data:image/svg+xml;base64,'.base64_encode($d->getBarcodeSVG($this->uid, 'QRCODE'));

        return [
            'uid' => $this->uid,
            'barcode' => $barcode,
            'item' => $this->whenLoaded('item', function () {
                return [
                    'uid' => $this->item->uid,
                    'name' => $this->item->name,
                ];
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'uid' => $this->unit->uid,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                ];
            }),
            'warehouse' => $this->whenLoaded('warehouse', function () {
                return [
                    'uid' => $this->warehouse->uid,
                    'name' => $this->warehouse->name,
                ];
            }),
            'rack' => $this->whenLoaded('rack', function () {
                return [
                    'uid' => $this->rack?->uid,
                    'name' => $this->rack?->name,
                ];
            }),
            'tank' => $this->whenLoaded('tank', function () {
                return [
                    'uid' => $this->tank?->uid,
                    'name' => $this->tank?->name,
                ];
            }),
            'stock_units' => $this->whenLoaded('stock_units', function () {
                return $this->stock_units->map(function ($unit) {
                    return [
                        'uid' => $unit->uid,
                        'qty' => $unit->qty,
                        'unit_uid' => $unit->unit?->uid,
                        'unit_name' => $unit->unit?->name,
                        'unit_symbol' => $unit->unit?->symbol,
                    ];
                });
            }),
            'qty' => $this->qty,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
