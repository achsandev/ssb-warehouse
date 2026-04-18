<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class WarehouseCashRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'               => $this->uid,
            'request_number'    => $this->request_number,
            'request_date'      => $this->request_date,
            'warehouse_uid'     => $this->warehouse?->uid,
            'warehouse_name'    => $this->warehouse_name,
            'cash_balance'      => (float) ($this->warehouse?->cash_balance ?? 0),
            'amount'            => (float) $this->amount,
            'attachment_path'   => $this->attachment_path,
            'attachment_name'   => $this->attachment_name,
            'attachment_url'    => $this->attachment_path
                ? Storage::disk('public')->url($this->attachment_path)
                : null,
            'notes'             => $this->notes,
            'status'            => $this->status,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'created_by_name'   => $this->created_by_name,
            'updated_by_name'   => $this->updated_by_name,
        ];
    }
}
