<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingDppFormulaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'             => $this->uid,
            'name'            => $this->name,
            'formula'         => $this->formula,
            'description'     => $this->description,
            'is_active'       => (bool) $this->is_active,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
