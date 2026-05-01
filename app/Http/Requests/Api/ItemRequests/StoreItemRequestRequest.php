<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\ItemRequests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validator untuk `POST /api/item-requests`.
 *
 * Dipisah dari `App\Http\Requests\ItemRequest\StoreRequest` (yang dipakai
 * SPA) supaya evolusi satu sisi tidak mengganggu sisi lain. Rule inti
 * identik dengan SPA karena DB schema sama, tapi lapisan API menambah:
 *   - Enum whitelist untuk `requirement` (cegah value tak terduga).
 *   - UUID format check sebelum query `exists` (fail-fast, murah).
 *   - Strip control chars dari free-text field (anti log-injection).
 *   - Cross-field check: tidak boleh ada item duplikat dalam satu request.
 *   - Cap jumlah baris detail untuk mencegah payload abusive.
 */
class StoreItemRequestRequest extends FormRequest
{
    /** Jumlah maksimum baris detail per satu item request. */
    public const MAX_DETAILS = 100;

    /** Panjang maksimal field string umum. */
    public const MAX_STRING_SHORT = 50;
    public const MAX_STRING_MEDIUM = 100;
    public const MAX_STRING_LONG = 255;
    public const MAX_DESCRIPTION = 500;

    /** Nilai maksimum qty satu baris — cegah integer overflow / abuse. */
    public const MAX_QTY = 999_999;

    /** Tanggal terendah yang diterima (filter data salah ketik ekstrim). */
    private const MIN_REQUEST_DATE = '2020-01-01';

    /** Requirement type yang sah (match enum bisnis existing). */
    private const ALLOWED_REQUIREMENTS = ['Direct Use', 'Replenishment'];

    public function authorize(): bool
    {
        // Authorization di-delegate ke middleware route:
        // `auth:sanctum` + `custom_permission:item_request.create`.
        return true;
    }

    /**
     * Normalize payload sebelum validasi:
     *   - `is_project` dikonversi eksplisit ke boolean supaya `"true"` /
     *     `"1"` / `"0"` dari form-urlencoded tidak diinterpretasi keliru.
     *   - Empty-string pada field optional → null (bentuk DB lebih bersih).
     *   - Karakter kontrol di-strip dari free-text field (anti log-injection
     *     saat pesan error / audit log diproses downstream).
     */
    protected function prepareForValidation(): void
    {
        $merges = [];

        if ($this->has('is_project')) {
            $merges['is_project'] = filter_var(
                $this->input('is_project'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE,
            );
        }

        foreach (['unit_code', 'wo_number', 'project_name'] as $field) {
            if ($this->input($field) === '') {
                $merges[$field] = null;
            }
        }

        foreach (['unit_code', 'wo_number', 'project_name', 'department_name'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && $value !== '') {
                $merges[$field] = $this->stripControlChars($value);
            }
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'requirement' => [
                'required', 'string',
                Rule::in(self::ALLOWED_REQUIREMENTS),
            ],
            'request_date' => [
                'required', 'date',
                'after_or_equal:'.self::MIN_REQUEST_DATE,
            ],
            'unit_code' => ['nullable', 'string', 'max:'.self::MAX_STRING_MEDIUM],
            'wo_number' => ['nullable', 'string', 'max:'.self::MAX_STRING_MEDIUM],

            // UUID format di-check dulu — kalau bukan UUID valid, `exists`
            // tidak perlu hit DB sama sekali.
            'warehouse_uid' => [
                'required', 'string', 'uuid',
                Rule::exists('wh_warehouse', 'uid'),
            ],

            'is_project' => ['required', 'boolean'],
            'project_name' => [
                'nullable', 'string', 'max:'.self::MAX_STRING_LONG,
                'required_if:is_project,true',
            ],
            'department_name' => [
                'required', 'string', 'max:'.self::MAX_STRING_LONG,
            ],

            // Detail barang.
            'details' => ['required', 'array', 'min:1', 'max:'.self::MAX_DETAILS],
            'details.*.item_uid' => [
                'required', 'string', 'uuid',
                Rule::exists('wh_items', 'uid'),
            ],
            'details.*.unit_uid' => [
                'required', 'string', 'uuid',
                Rule::exists('wh_item_units', 'uid'),
            ],
            'details.*.qty' => [
                'required', 'integer', 'min:1', 'max:'.self::MAX_QTY,
            ],
            'details.*.description' => [
                'nullable', 'string', 'max:'.self::MAX_DESCRIPTION,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requirement.in' => 'The requirement must be one of: '
                .implode(', ', self::ALLOWED_REQUIREMENTS).'.',
            'details.max' => 'The details cannot exceed '.self::MAX_DETAILS
                .' entries per request.',
        ];
    }

    /**
     * Label field yang lebih enak dibaca di pesan error API.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'warehouse_uid' => 'warehouse',
            'details.*.item_uid' => 'item',
            'details.*.unit_uid' => 'unit',
            'details.*.qty' => 'quantity',
        ];
    }

    /**
     * Cross-field validation: cegah item duplikat dalam satu request.
     * Konsisten dengan business rule yang diterapkan SPA — qty item yang
     * sama harus digabung di satu baris, bukan di-split ke beberapa baris.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $details = $this->input('details', []);
            if (! is_array($details)) {
                return;
            }

            $seen = [];
            foreach ($details as $idx => $row) {
                $itemUid = $row['item_uid'] ?? null;
                if (! is_string($itemUid) || $itemUid === '') {
                    continue;
                }

                if (isset($seen[$itemUid])) {
                    $v->errors()->add(
                        "details.$idx.item_uid",
                        'Duplicate item — merge its qty into the existing row or choose a different item.',
                    );
                }
                $seen[$itemUid] = true;
            }
        });
    }

    private function stripControlChars(string $value): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    }
}
