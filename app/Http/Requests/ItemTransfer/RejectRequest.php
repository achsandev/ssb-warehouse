<?php

namespace App\Http\Requests\ItemTransfer;

use Illuminate\Foundation\Http\FormRequest;

class RejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reject_notes' => 'required|string|min:3|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reject_notes.required' => 'Alasan penolakan wajib diisi.',
            'reject_notes.min'      => 'Alasan penolakan minimal 3 karakter.',
        ];
    }
}
