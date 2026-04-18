<?php

namespace App\Http\Requests\SettingApproverItemRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust as needed
    }

    public function rules(): array
    {
        return [
            'requester_role_id' => 'sometimes|required|exists:roles,id',
            'requester_role_name' => 'sometimes|required|string',
            'approver_role_id' => 'sometimes|required|exists:roles,id',
            'approver_role_name' => 'sometimes|required|string',
        ];
    }
}
