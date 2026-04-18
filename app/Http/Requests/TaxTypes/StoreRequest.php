<?php

namespace App\Http\Requests\TaxTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:wh_tax_types,name',
            'description' => 'nullable|string',
            'formula_type' => 'required|string|in:formula,percentage,manual',
            'formula' => 'nullable|string|max:1000|required_if:formula_type,formula,percentage',
            'uses_dpp' => 'nullable|boolean',
        ];
    }
}
