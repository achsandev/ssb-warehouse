<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemUsageDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'  => $this->uid,
            'item' => $this->whenLoaded('item', fn () => [
                'uid'         => $this->item->uid,
                'name'        => $this->item->name,
                // Field tambahan untuk print-out (kode + part number + harga).
                'code'        => $this->item->code ?? null,
                'part_number' => $this->item->part_number ?? null,
                'price'       => $this->item->price ?? null,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'uid'    => $this->unit->uid,
                'name'   => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
            'qty'             => $this->qty,
            'usage_qty'       => $this->usage_qty,
            'description'     => $this->description,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
