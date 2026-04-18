<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared filter request untuk semua report (Stock Usage, Stock Adjustment, dll).
 * Status dibuat string bebas supaya setiap report bisa punya kamus status sendiri,
 * tetapi tetap dibatasi panjang & tipe agar aman dari injection.
 */
class FilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status'     => ['nullable', 'string', 'max:50'],
            'search'     => ['nullable', 'string', 'max:100'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'between:1,500'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'End date must be on or after start date.',
        ];
    }
}
