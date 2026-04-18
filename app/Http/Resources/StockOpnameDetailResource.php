<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'            => $this->uid,
            'stock_unit_uid' => $this->stockUnit?->uid,
            'item_name'      => $this->item_name,
            'unit_symbol'    => $this->unit_symbol,
            'warehouse_name' => $this->warehouse_name,
            'rack_name'      => $this->rack_name,
            'system_qty'     => $this->system_qty,
            'actual_qty'     => $this->actual_qty,
            'difference_qty' => $this->difference_qty,
            'notes'          => $this->notes,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
