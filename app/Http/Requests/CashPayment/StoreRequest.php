<?php

namespace App\Http\Requests\CashPayment;

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
            'payment_date'  => 'required|date',
            'warehouse_uid' => 'required|string|exists:wh_warehouse,uid',
            'description'   => 'required|string',
            'amount'        => 'required|numeric|min:1',
            'spk'           => 'mimes:pdf|max:10240|nullable',
            'attachment'    => 'mimes:pdf|max:10240|nullable',
            'notes'         => 'nullable|string',
        ];
    }
}
