<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiveItemDetailResource extends JsonResource
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
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'uid' => $this->supplier->uid,
                    'name' => $this->supplier->name,
                ];
            }),
            'qty' => $this->qty,
            'price' => $this->price,
            'total' => $this->total,
            'qty_received' => $this->qty_received,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,
            'updated_by_id' => $this->updated_by_id,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
