<?php

declare(strict_types=1);

namespace App\Http\Requests\SettingMinWarehouseCash;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validator untuk update min_cash sebuah warehouse.
 *
 * Rules:
 *   - `min_cash` boleh null (artinya: hapus threshold / nonaktifkan).
 *   - Bila ada nilai: harus numerik, tidak negatif, max 9.999.999.999.999,99
 *     (sesuai schema decimal(15,2)).
 */
class UpdateRequest extends FormRequest
{
    public const MAX_AMOUNT = 9_999_999_999_999.99;

    public function authorize(): bool
    {
        // Authorization dihandle middleware route (custom_permission).
        return true;
    }

    /**
     * Normalize input sebelum validasi:
     *   - String kosong / "null" / "0" yang tidak diinginkan → null bila admin
     *     ingin menonaktifkan threshold via UI.
     *   - Comma decimal (mis. "1.000,50") di-konversi ke titik supaya
     *     numeric() validator-nya lolos.
     */
    protected function prepareForValidation(): void
    {
        $value = $this->input('min_cash');

        if ($value === '' || $value === null) {
            $this->merge(['min_cash' => null]);
            return;
        }

        if (is_string($value)) {
            // Hapus thousand separator (titik atau koma sebagai grouping),
            // pastikan decimal pakai titik. Contoh: "1.500,50" → "1500.50".
            $cleaned = str_replace(['.', ' '], '', $value);
            $cleaned = str_replace(',', '.', $cleaned);
            $this->merge(['min_cash' => $cleaned]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'min_cash' => [
                'nullable',
                'numeric',
                'min:0',
                'max:'.self::MAX_AMOUNT,
            ],
        ];
    }
}
