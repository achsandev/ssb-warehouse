<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderDetailResource extends JsonResource
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
            'purchase_order' => $this->whenLoaded('purchase_order', function () {
                return [
                    'uid' => $this->purchase_order->uid,
                    'po_number' => $this->purchase_order->po_number,
                    'po_date' => $this->purchase_order->po_date,
                    'project_name' => $this->purchase_order->project_name,
                ];
            }),
            'item' => $this->whenLoaded('item', function () {
                return [
                    'uid' => $this->item->uid,
                    'code' => $this->item->code,
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
            'created_at' => $this->created_at,
            'created_by_name' => $this->created_by_name,
            'updated_at' => $this->updated_at,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
