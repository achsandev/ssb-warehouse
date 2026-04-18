<?php

namespace App\Http\Requests\CashPurchase;

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
            'purchase_date'  => 'required|date',
            'warehouse_uid'  => 'required|exists:wh_warehouse,uid',
            'po_uid'         => 'required|exists:wh_purchase_order,uid',
            'notes'          => 'nullable|string',
            'status'         => 'required|string',
        ];
    }
}
