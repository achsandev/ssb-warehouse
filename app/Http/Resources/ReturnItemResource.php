<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'           => $this->uid,
            'return_number' => $this->return_number,
            'return_date'   => $this->return_date,
            'project_name'  => $this->project_name,
            'purchase_order' => $this->whenLoaded('purchaseOrder', fn () => [
                'uid'       => $this->purchaseOrder->uid,
                'po_number' => $this->purchaseOrder->po_number,
            ]),
            'status'  => $this->status,
            'details' => ReturnItemDetailResource::collection($this->whenLoaded('details')),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
