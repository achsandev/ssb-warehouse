<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemCategoriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'name' => $this->name,
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,
            'updated_by_id' => $this->updated_by_id,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
