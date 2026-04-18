<?php

namespace App\Http\Requests\SettingDppFormula;

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
            'name'        => 'required|string|max:255|unique:wh_setting_dpp_formula,name',
            'formula'     => ['required', 'string', 'max:1000', 'regex:/^[0-9xX+\-*\/().% ]+$/'],
            'description' => 'nullable|string|max:2000',
            'is_active'   => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'formula.regex' => 'Formula hanya boleh berisi angka, huruf x, operator + - * /, tanda kurung, titik, persen, dan spasi.',
        ];
    }
}
