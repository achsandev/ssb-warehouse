<?php

namespace App\Http\Requests\StockOpname;

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
            'opname_date'                    => 'required|date',
            'notes'                          => 'nullable|string',
            'details'                        => 'required|array|min:1|max:200',
            'details.*.stock_unit_uid'       => 'required|string|exists:wh_stock_units,uid',
            'details.*.notes'                => 'nullable|string',
        ];
    }
}
