<?php

namespace App\Http\Requests\CashPayment;

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
            'payment_date'  => 'required|date',
            'warehouse_uid' => 'required|string|exists:wh_warehouse,uid',
            'description'   => 'required|string',
            'amount'        => 'required|numeric|min:1',
            'notes'         => 'nullable|string',
            'status'        => 'required|string',
        ];
    }
}
