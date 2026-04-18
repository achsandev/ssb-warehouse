<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'            => $this->uid,
            'opname_number'  => $this->opname_number,
            'opname_date'    => $this->opname_date,
            'notes'          => $this->notes,
            'status'         => $this->status,
            'details'        => StockOpnameDetailResource::collection($this->whenLoaded('details')),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            'created_by_name' => $this->created_by_name,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
