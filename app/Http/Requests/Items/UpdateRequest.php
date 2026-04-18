<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => 'required|string|max:255',
            'brand_uid' => 'required|string|exists:wh_item_brands,uid',
            'category_uid' => 'required|string|exists:wh_item_categories,uid',
            'unit_uid' => 'required|string|exists:wh_item_units,uid',
            'movement_category_uid' => 'required|string|exists:wh_movement_categories,uid',
            'valuation_method_uid' => 'required|string|exists:wh_valuation_methods,uid',
            'material_group_uid' => 'required|string|exists:wh_material_groups,uid',
            'sub_material_group_uid' => 'required|string|exists:wh_sub_material_groups,uid',
            'supplier_uid' => 'nullable|string|exists:wh_supplier,uid',
            'request_types_uid' => 'required|array|min:1',
            'request_types_uid.*' => 'string|exists:wh_request_types,uid',
            'unit_types_uid' => 'required|array|min:1',
            'unit_types_uid.*' => 'string|exists:wh_usage_units,uid',
            'min_qty' => 'required|integer|min:0',
            'part_number' => 'required|string',
            'interchange_part' => 'string|nullable',
            'image' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_string($value)) {
                        // biarkan jika image sudah berupa path string
                        return;
                    }

                    if (! request()->hasFile($attribute)) {
                        return;
                    }

                    $file = request()->file($attribute);
                    if (! $file->isValid()) {
                        $fail('The '.$attribute.' field must be a valid file.');
                    } elseif (! in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                        $fail('The '.$attribute.' field must be a file of type: jpg, jpeg, png.');
                    }
                },
            ],
            'price' => 'string|nullable',
            'exp_date' => 'date|nullable',
            'additional_info' => 'string|nullable',
        ];
    }
}
