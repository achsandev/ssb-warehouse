<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemRequestResource extends JsonResource
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
            'requirement' => $this->requirement,
            'request_number' => $this->request_number,
            'request_date' => $this->request_date,
            'unit_code' => $this->unit_code,
            'wo_number' => $this->wo_number,
            'warehouse' => $this->whenLoaded('warehouse', function () {
                return [
                    'uid'  => $this->warehouse?->uid,
                    'name' => $this->warehouse?->name,
                ];
            }),
            'is_project' => (bool) $this->is_project,
            'project_name' => $this->project_name,
            'department_name' => $this->department_name,
            'created_by_role_id' => $this->created_by_role_id,
            'details' => $this->whenLoaded('item_request_detail', function () {
                return ItemRequestDetailResource::collection($this->item_request_detail);
            }),
            'status' => $this->status,
            'reject_reason' => $this->reject_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
            'approver' => $this->whenLoaded('approverSetting', function () {
                return [
                    'role_id' => $this->approverSetting->approver_role_id ?? null,
                    'role_name' => $this->approverSetting->approver_role_name ?? null,
                ];
            }),
        ];
    }
}
