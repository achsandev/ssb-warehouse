<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialGroupsResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'sub_material_groups' => $this->whenLoaded('sub_material_group', function () {
                return $this->sub_material_group->map(function ($sub_material_group) {
                    return [
                        'uid' => $sub_material_group->uid,
                        'code' => $sub_material_group->code,
                        'name' => $sub_material_group->name,
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
            'code' => $this->code,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
