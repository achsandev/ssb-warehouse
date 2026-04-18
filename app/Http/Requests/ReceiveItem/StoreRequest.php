<?php

namespace App\Http\Requests\ReceiveItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receipt_date' => 'required|date',
            'project_name' => 'string|nullable',
            'purchase_order_uid' => 'required|string|exists:wh_purchase_order,uid',
            'warehouse_uid' => 'required|string|exists:wh_warehouse,uid',
            'shipping_cost' => 'nullable|numeric|min:0',
            'additional_info' => 'string|nullable',
            'details' => 'required|array|min:1',
            'details.*.item_uid' => 'required|string|exists:wh_items,uid',
            'details.*.unit_uid' => 'required|string|exists:wh_item_units,uid',
            'details.*.supplier_uid' => 'required|string|exists:wh_supplier,uid',
            'details.*.qty' => 'required|integer|min:1',
            'details.*.price' => 'required|numeric|min:0',
            'details.*.total' => 'required|numeric|min:0',
            'details.*.qty_received' => 'required|integer|min:1',
        ];
    }
}
