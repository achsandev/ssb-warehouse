<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'id_karyawan' => 'nullable|integer',
            'nik' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'sub_department' => 'nullable|string|max:255',
            'position' => 'required|string|max:255',
            'direct_supervisor_id' => 'nullable|exists:users,id',
            'direct_supervisor_position' => 'nullable|string|max:255',
        ];
    }
}
