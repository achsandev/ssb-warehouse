<?php

namespace App\Http\Requests\StockAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_date'              => 'required|date',
            'stock_opname_uid'             => 'nullable|string|exists:wh_stock_opname,uid',
            'notes'                        => 'nullable|string',
            'status'                       => 'required|string',
            'details'                      => 'required|array|min:1',
            'details.*.stock_unit_uid'     => 'required|string|exists:wh_stock_units,uid',
            'details.*.adjustment_qty'     => 'required|numeric',
            'details.*.notes'              => 'nullable|string',
        ];
    }
}
