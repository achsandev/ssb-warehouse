<?php

namespace App\Http\Requests\ReturnItem;

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
            'purchase_order_uid' => 'required|string|exists:wh_purchase_order,uid',
            'return_date'        => 'required|date',
            'project_name'       => 'nullable|string|max:255',
            'details'            => 'required|array|min:1',
            'details.*.item_uid' => 'required|string|exists:wh_items,uid',
            'details.*.unit_uid' => 'required|string|exists:wh_item_units,uid',
            'details.*.qty'      => 'required|numeric|min:0',
            'details.*.return_qty'  => 'required|numeric|min:0.01',
            'details.*.description' => 'nullable|string',
        ];
    }
}
