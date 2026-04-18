<?php

namespace App\Http\Requests\SubMaterialGroups;

use App\Models\MaterialGroups;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $material_group_id = null;

        if ($this->filled('material_group_uid')) {
            $material_group_id = MaterialGroups::where('uid', $this->input('material_group_uid'))->value('id');
        }

        return [
            'material_group_uid' => 'required|string|exists:wh_material_groups,uid',
            'code' => 'required|string|max:5|unique:wh_sub_material_groups,code',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wh_sub_material_groups', 'name')
                    ->where(fn ($query) => $query->where('material_group_id', $material_group_id)),
            ],
        ];
    }
}
