<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'phone_number' => $this->phone_number,
            'npwp_number' => $this->npwp_number,
            'pic_name' => $this->pic_name,
            'email' => $this->email,
            'payment_method' => $this->whenLoaded('payment_method', function () {
                return [
                    'uid' => $this->payment_method->uid,
                    'name' => $this->payment_method->name,
                ];
            }),
            'payment_duration' => $this->whenLoaded('payment_duration', function () {
                return [
                    'uid' => $this->payment_duration->uid,
                    'name' => $this->payment_duration->name,
                ];
            }),
            'tax_types' => $this->whenLoaded('tax_types', function () {
                return $this->tax_types->map(function ($type) {
                    return [
                        'uid'          => $type->uid,
                        'name'         => $type->name,
                        'formula_type' => $type->formula_type,
                        'formula'      => $type->formula,
                        'uses_dpp'     => (bool) $type->uses_dpp,
                    ];
                });
            }),
            'additional_info' => $this->additional_info,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by_id' => $this->created_by_id,
            'created_by_name' => $this->created_by_name,
            'updated_by_id' => $this->updated_by_id,
            'updated_by_name' => $this->updated_by_name,
        ];
    }
}
