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

            // Daftar key kolom yang ingin di-include di hasil export.
            // Format: ['usage_date', 'item_name', ...]. Validasi panjang
            // dilakukan di sini, whitelist value-nya divalidasi di
            // controller via `selectedColumns()` terhadap COLUMN_DEFS
            // masing-masing report (mencegah column-name injection).
            'columns'   => ['sometimes', 'array', 'max:50'],
            'columns.*' => ['string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'End date must be on or after start date.',
        ];
    }

    /**
     * Helper: dapatkan list kolom yang valid (intersect dengan whitelist),
     * fallback ke semua kolom whitelist kalau user tidak kirim apa-apa.
     *
     * @param  array<int, string>  $whitelist  Daftar key kolom valid (urutan jadi default).
     * @return array<int, string>
     */
    public function selectedColumns(array $whitelist): array
    {
        $requested = $this->input('columns');
        if (! is_array($requested) || $requested === []) {
            return $whitelist;
        }

        // Intersect + preserve whitelist ordering, reject value tak dikenal.
        $set = array_flip(array_map('strval', $requested));
        $valid = array_values(array_filter(
            $whitelist,
            static fn (string $k): bool => isset($set[$k]),
        ));

        return $valid === [] ? $whitelist : $valid;
    }
}
