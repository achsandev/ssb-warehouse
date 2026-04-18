<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemTransferDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'  => $this->uid,
            'item' => $this->whenLoaded('item', fn () => [
                'uid'  => $this->item->uid,
                'name' => $this->item->name,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'uid'    => $this->unit->uid,
                'name'   => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
            'qty'             => $this->qty,
            'description'     => $this->description,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
