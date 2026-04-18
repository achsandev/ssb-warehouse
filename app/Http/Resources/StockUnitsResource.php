<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockUnitsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'stock' => $this->whenLoaded('stock', function () {
                return [
                    'uid' => $this->stock->uid,
                    'item_name' => $this->stock->item_name,
                    'warehouse_name' => $this->stock->warehouse_name,
                    'rack_name' => $this->stock?->rack_name,
                    'tank_name' => $this->stock?->tank_name,
                ];
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'uid' => $this->unit->uid,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                ];
            }),
            'qty' => $this->qty,
        ];
    }
}
