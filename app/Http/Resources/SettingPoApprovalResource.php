<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingPoApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'level' => $this->level,
            'role' => $this->whenLoaded('role', function () {
                return [
                    'uid' => $this->role->uid,
                    'name' => $this->role->name,
                ];
            }),
            'min_amount' => $this->min_amount,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
