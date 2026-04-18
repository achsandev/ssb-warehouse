<?php

namespace App\Http\Requests\Supplier;

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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wh_supplier', 'name')
                    ->ignore($this->route('uid'), 'uid'),
            ],
            'address' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
            'npwp_number' => 'nullable|string|max:20',
            'pic_name' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'payment_method_uid' => 'required|string|exists:wh_payment_methods,uid',
            'payment_duration_uid' => 'required|string|exists:wh_payment_duration,uid',
            'tax_type_uid' => 'required|array|min:1',
            'tax_type_uid.*' => 'string|exists:wh_tax_types,uid',
            'additional_info' => 'string|nullable',
        ];
    }
}
