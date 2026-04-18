<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'                  => $this->uid,
            'adjustment_number'    => $this->adjustment_number,
            'adjustment_date'      => $this->adjustment_date,
            'stock_opname_uid'     => $this->stockOpname?->uid,
            'stock_opname_number'  => $this->stock_opname_number,
            'notes'                => $this->notes,
            'status'               => $this->status,
            'details'              => StockAdjustmentDetailResource::collection($this->whenLoaded('details')),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
            'created_by_name'      => $this->created_by_name,
            'updated_by_name'      => $this->updated_by_name,
        ];
    }
}
