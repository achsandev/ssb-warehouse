<?php

namespace App\Http\Requests\MaterialGroups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:5',
                Rule::unique('wh_material_groups', 'code')
                    ->ignore($this->route('uid'), 'uid'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wh_material_groups', 'name')
                    ->ignore($this->route('uid'), 'uid'),
            ],
        ];
    }
}
