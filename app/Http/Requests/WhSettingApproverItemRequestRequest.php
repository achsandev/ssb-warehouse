<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhSettingApproverItemRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust authorization as needed
    }

    public function rules(): array
    {
        return [
            'requester_role_id' => 'required|exists:roles,id',
            'requester_role_name' => 'required|string',
            'approver_role_id' => 'required|exists:roles,id',
            'approver_role_name' => 'required|string',
        ];
    }
}
