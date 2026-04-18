<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'             => $this->uid,
            'transfer_number' => $this->transfer_number,
            'transfer_date'   => $this->transfer_date,

            // Source
            'from_warehouse' => $this->whenLoaded('fromWarehouse', fn () => [
                'uid'  => $this->fromWarehouse->uid,
                'name' => $this->fromWarehouse->name,
            ]),
            'from_rack' => $this->whenLoaded('fromRack', fn () => $this->fromRack ? [
                'uid'  => $this->fromRack->uid,
                'name' => $this->fromRack->name,
            ] : null),
            'from_tank' => $this->whenLoaded('fromTank', fn () => $this->fromTank ? [
                'uid'  => $this->fromTank->uid,
                'name' => $this->fromTank->name,
            ] : null),

            // Destination
            'to_warehouse' => $this->whenLoaded('toWarehouse', fn () => [
                'uid'  => $this->toWarehouse->uid,
                'name' => $this->toWarehouse->name,
            ]),
            'to_rack' => $this->whenLoaded('toRack', fn () => $this->toRack ? [
                'uid'  => $this->toRack->uid,
                'name' => $this->toRack->name,
            ] : null),
            'to_tank' => $this->whenLoaded('toTank', fn () => $this->toTank ? [
                'uid'  => $this->toTank->uid,
                'name' => $this->toTank->name,
            ] : null),

            'notes'        => $this->notes,
            'reject_notes' => $this->reject_notes,
            'status'       => $this->status,

            // Chain transfer info
            'has_pending_displacement' => (bool) $this->has_pending_displacement,
            'parent_transfer' => $this->whenLoaded('parentTransfer', fn () => $this->parentTransfer ? [
                'uid'             => $this->parentTransfer->uid,
                'transfer_number' => $this->parentTransfer->transfer_number,
                'status'          => $this->parentTransfer->status,
            ] : null),
            'child_transfers' => $this->whenLoaded('childTransfers', fn () =>
                $this->childTransfers->map(fn ($c) => [
                    'uid'             => $c->uid,
                    'transfer_number' => $c->transfer_number,
                    'status'          => $c->status,
                ])
            ),

            'details' => ItemTransferDetailResource::collection($this->whenLoaded('details')),

            // Audit logs
            'logs' => $this->whenLoaded('logs', fn () =>
                $this->logs->map(fn ($log) => [
                    'uid'         => $log->uid,
                    'action'      => $log->action,
                    'from_status' => $log->from_status,
                    'to_status'   => $log->to_status,
                    'notes'       => $log->notes,
                    'metadata'    => $log->metadata,
                    'actor_name'  => $log->actor_name,
                    'actor_role'  => $log->actor_role,
                    'created_at'  => $log->created_at,
                ])
            ),

            'approved_by_name' => $this->approved_by_name,
            'approved_at'      => $this->approved_at,
            'cancelled_at'     => $this->cancelled_at,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
            'created_by_name'  => $this->created_by_name,
            'updated_by_name'  => $this->updated_by_name,
        ];
    }
}
