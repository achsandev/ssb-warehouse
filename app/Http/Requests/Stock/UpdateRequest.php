<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
            'item_uid' => 'required|string|exists:wh_items,uid',
            'warehouse_uid' => 'required|string|exists:wh_warehouse,uid',
            'rack_uid' => 'nullable|string|exists:wh_rack,uid',
            'tank_uid' => 'nullable|string|exists:wh_tank,uid',
            'unit_uid' => 'nullable|string|exists:wh_item_units,uid',
            'qty' => 'nullable|integer',
        ];
    }
}
