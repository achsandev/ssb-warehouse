<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CashPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'             => $this->uid,
            'purchase_number' => $this->purchase_number,
            'purchase_date'   => $this->purchase_date,
            'warehouse_uid'   => $this->whenLoaded('warehouse', fn () => $this->warehouse->uid),
            'warehouse_name'  => $this->warehouse_name,
            'cash_balance'    => $this->whenLoaded('warehouse', fn () => $this->warehouse->cash_balance, 0),
            'po_uid'          => $this->whenLoaded('purchaseOrder', fn () => $this->purchaseOrder->uid),
            'po_number'       => $this->po_number,
            'po_total_amount' => $this->po_total_amount,
            'po_details'      => $this->whenLoaded('purchaseOrder', function () {
                return $this->purchaseOrder->details->map(fn ($d) => [
                    'item_name'    => $d->item?->name ?? null,
                    'unit_symbol'  => $d->unit?->symbol ?? null,
                    'qty'          => $d->qty,
                    'price'        => $d->price,
                    'total'        => $d->total,
                ]);
            }),
            'notes'           => $this->notes,
            'status'          => $this->status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
