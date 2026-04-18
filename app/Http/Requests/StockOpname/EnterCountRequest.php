<?php

namespace App\Http\Requests\StockOpname;

use Illuminate\Foundation\Http\FormRequest;

class EnterCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'details'                  => 'required|array|min:1',
            'details.*.uid'            => 'required|string|exists:wh_stock_opname_detail,uid',
            'details.*.actual_qty'     => 'required|numeric|min:0',
            'details.*.notes'          => 'nullable|string',
        ];
    }
}
