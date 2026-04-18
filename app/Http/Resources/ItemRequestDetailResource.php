<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemRequestDetailResource extends JsonResource
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
                    'request_date' => $this->item_request->request_date,
                    'project_name' => $this->item_request->project_name,
                    'department_name' => $this->item_request->department_name,
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
            'qty' => $this->qty,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
