<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiveItemResource extends JsonResource
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
            'receipt_number' => $this->receipt_number,
            'receipt_date' => $this->receipt_date,
            'project_name' => $this->project_name,
            'purchase_order' => $this->whenLoaded('purchase_order', function () {
                return [
                    'uid' => $this->purchase_order->uid,
                    'po_number' => $this->purchase_order->po_number,
                ];
            }),
            'warehouse' => $this->whenLoaded('warehouse', function () {
                return [
                    'uid' => $this->warehouse->uid,
                    'name' => $this->warehouse->name,
                ];
            }),
            'details' => ReceiveItemDetailResource::collection($this->whenLoaded('details')),
            'shipping_cost' => (int) ($this->shipping_cost ?? 0),
            'status' => $this->status,
            'reject_reason' => $this->reject_reason,
            'additional_info' => $this->additional_info,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,
            'updated_by_id' => $this->updated_by_id,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
