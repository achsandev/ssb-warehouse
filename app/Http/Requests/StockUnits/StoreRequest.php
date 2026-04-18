<?php

namespace App\Http\Requests\StockUnits;

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
            'stock_uid' => 'required|string|exists:wh_stocks,uid',
            'base_unit_uid' => 'required|string|exists:wh_item_units,uid',
            'derived_unit_uid' => 'required|string|exists:wh_item_units,uid',
            'convert_qty' => 'required|integer',
            'converted_qty' => 'required|integer',
        ];
    }
}
