<?php

namespace App\Http\Requests\ItemRequest;

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
    protected function prepareForValidation(): void
    {
        if ($this->has('is_project')) {
            $this->merge([
                'is_project' => filter_var(
                    $this->input('is_project'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'requirement'     => 'required|string|max:50',
            'request_date'    => 'required|date',
            'unit_code'       => 'nullable|string|max:100',
            'wo_number'       => 'nullable|string|max:100',
            'warehouse_uid'   => 'required|string|exists:wh_warehouse,uid',
            'is_project'      => 'required|boolean',
            'project_name'    => 'nullable|string|max:255|required_if:is_project,true',
            'department_name' => 'required|string|max:255',
            'status'          => 'required|string',
            'reject_reason'   => 'nullable|string|max:1000|required_if:status,Rejected',
            'details'                => 'required|array|min:1',
            'details.*.item_uid'     => 'required|string|exists:wh_items,uid',
            'details.*.unit_uid'     => 'required|string|exists:wh_item_units,uid',
            'details.*.qty'          => 'required|integer|min:1',
            'details.*.description'  => 'nullable|string|max:500',
        ];
    }
}
