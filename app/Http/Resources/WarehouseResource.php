<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'additional_info' => $this->additional_info,
            'racks' => $this->whenLoaded('rack', function () {
                return $this->rack->map(function ($rack) {
                    return [
                        'uid' => $rack->uid,
                        'name' => $rack->name,
                    ];
                });
            }),
            'tanks' => $this->whenLoaded('tank', function () {
                return $this->tank->map(function ($tank) {
                    return [
                        'uid' => $tank->uid,
                        'name' => $tank->name,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }

    public function basicInfo(): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'address' => $this->address,
            'additional_info' => $this->additional_info,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
