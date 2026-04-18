<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CashPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'             => $this->uid,
            'payment_number'  => $this->payment_number,
            'payment_date'    => $this->payment_date,
            'warehouse_uid'   => $this->warehouse?->uid,
            'warehouse_name'  => $this->warehouse_name,
            'cash_balance'    => (float) ($this->warehouse?->cash_balance ?? 0),
            'description'     => $this->description,
            'amount'          => (float) $this->amount,
            'spk_path'        => $this->spk_path,
            'spk_name'        => $this->spk_name,
            'spk_url'         => $this->spk_path
                ? Storage::disk('public')->url($this->spk_path)
                : null,
            'attachment_path' => $this->attachment_path,
            'attachment_name' => $this->attachment_name,
            'attachment_url'  => $this->attachment_path
                ? Storage::disk('public')->url($this->attachment_path)
                : null,
            'notes'           => $this->notes,
            'status'          => $this->status,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
