<?php

namespace App\Http\Requests\ItemTransfer;

use Illuminate\Foundation\Http\FormRequest;

class CancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:500',
        ];
    }
}
