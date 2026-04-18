<?php

namespace App\Http\Requests\Tank;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Nama tangki hanya wajib unik di dalam 1 warehouse — nama yang sama
        // boleh digunakan di warehouse yang berbeda.
        $warehouseId = Warehouse::query()
            ->where('uid', $this->input('warehouse_uid'))
            ->value('id');

        return [
            'warehouse_uid' => 'required|string|exists:wh_warehouse,uid',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wh_tank', 'name')
                    ->where(fn ($q) => $q->where('warehouse_id', $warehouseId)),
            ],
            'additional_info' => 'string|nullable',
        ];
    }
}
