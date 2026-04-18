<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.($this->user ? $this->user->id : 'null'),
            'password' => $this->isMethod('post') ? 'required|string|min:6' : 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'user_detail' => 'nullable|array',
            'user_detail.id_karyawan' => 'nullable|integer',
            'user_detail.nik' => 'required_with:user_detail|string',
            'user_detail.name' => 'required_with:user_detail|string',
            'user_detail.department' => 'required_with:user_detail|string',
            'user_detail.sub_department' => 'nullable|string',
            'user_detail.position' => 'required_with:user_detail|string',
            'user_detail.direct_supervisor_id' => 'nullable|integer',
            'user_detail.direct_supervisor_position' => 'nullable|string',
        ];
    }
}
