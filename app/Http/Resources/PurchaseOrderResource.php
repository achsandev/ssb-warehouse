<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
            'item_request' => $this->whenLoaded('item_request', function () {
                return [
                    'uid' => $this->item_request->uid,
                    'request_number' => $this->item_request->request_number,
                    'project_name' => $this->item_request->project_name,
                    'wo_number' => $this->item_request->wo_number,
                ];
            }),
            'wo_number' => $this->whenLoaded('item_request', fn () => $this->item_request?->wo_number),
            'po_number' => $this->po_number,
            'po_date' => $this->po_date,
            'project_name' => $this->project_name,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'details' => PurchaseOrderDetailResource::collection($this->whenLoaded('details')),
            'approval_logs' => $this->whenLoaded('approval_logs', function () {
                return $this->approval_logs->map(fn ($log) => [
                    'uid'              => $log->uid,
                    'approval_level'   => $log->approval_level,
                    'role_name'        => $log->role_name,
                    'status'           => $log->status,
                    'notes'            => $log->notes,
                    'approved_by_name' => $log->approved_by_name,
                    'created_at'       => $log->created_at,
                ]);
            }),
            'created_at' => $this->created_at,
            'created_by_name' => $this->created_by_name,
            'updated_at' => $this->updated_at,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
