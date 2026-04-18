<?php

namespace App\Http\Requests\WarehouseCashRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_date' => 'required|date',
            'warehouse_uid' => 'required|string|exists:wh_warehouse,uid',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
            'attachment' => 'mimes:pdf,jpg,jpeg,png|max:10240|nullable',
        ];
    }
}
