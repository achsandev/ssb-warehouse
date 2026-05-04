<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'          => $this->uid,
            'usage_number' => $this->usage_number,
            'usage_date'   => $this->usage_date,
            'project_name'   => $this->project_name,
            'recipient_name' => $this->recipient_name,
            'item_request' => $this->whenLoaded('itemRequest', fn () => [
                'uid'             => $this->itemRequest->uid,
                'request_number'  => $this->itemRequest->request_number,
                // Field tambahan untuk print-out:
                'department_name' => $this->itemRequest->department_name,
                'unit_code'       => $this->itemRequest->unit_code,
                'wo_number'       => $this->itemRequest->wo_number,
                'is_project'      => (bool) ($this->itemRequest->is_project ?? false),
            ]),
            'status'  => $this->status,
            'details' => ItemUsageDetailResource::collection($this->whenLoaded('details')),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
