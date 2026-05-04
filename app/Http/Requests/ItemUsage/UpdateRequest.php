<?php

namespace App\Http\Requests\ItemUsage;

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
            'item_request_uid' => 'required|string|exists:wh_item_request,uid',
            'usage_date' => 'required|date',
            'project_name' => 'nullable|string|max:255',
            'recipient_name' => 'nullable|string|max:150',
            'status' => 'required|string',
            'details' => 'required|array|min:1',
            'details.*.item_uid' => 'required|string|exists:wh_items,uid',
            'details.*.unit_uid' => 'required|string|exists:wh_item_units,uid',
            'details.*.qty' => 'required|numeric|min:0',
            'details.*.usage_qty' => 'required|numeric|min:0.01',
            'details.*.description' => 'nullable|string',
        ];
    }
}
