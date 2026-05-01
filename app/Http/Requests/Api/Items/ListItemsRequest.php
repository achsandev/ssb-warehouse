<?php

namespace App\Http\Requests\Api\Items;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validator untuk `GET /api/items`.
 *
 * Tujuan utama: defense-in-depth terhadap parameter yang menentukan shape
 * query database. Rule di sini bukan pengganti logic di controller, tapi
 * baris pertahanan pertama — controller TETAP re-validasi whitelist sort
 * saat membangun query.
 */
class ListItemsRequest extends FormRequest
{
    /** Whitelist kolom yang boleh dipakai untuk sort (mirror controller). */
    public const ALLOWED_SORT = [
        'code',
        'name',
        'brand_name',
        'item_category_name',
        'unit_name',
        'min_qty',
        'price',
        'created_at',
        'updated_at',
    ];

    /** Batas atas `per_page` untuk mencegah data exfiltration via query besar. */
    public const PER_PAGE_MAX = 100;

    public function authorize(): bool
    {
        return true; // Authorization dihandle middleware auth:sanctum + custom_permission.
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.self::PER_PAGE_MAX],
            'sort_by'  => ['sometimes', 'string', 'in:'.implode(',', self::ALLOWED_SORT)],
            'sort_dir' => ['sometimes', 'string', 'in:asc,desc'],
            // Search dibatasi panjang untuk menghindari payload abusive + SQLi
            // attempts. Karakter kontrol dipotong lewat prepareForValidation.
            'search'   => ['sometimes', 'nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort_by.in' => 'The sort_by must be one of: '.implode(', ', self::ALLOWED_SORT).'.',
        ];
    }

    /**
     * Sanitize input sebelum validasi:
     *  - Strip karakter kontrol dari `search` untuk mencegah log-injection /
     *    header-injection pada SIEM downstream.
     *  - Trim whitespace sehingga string hanya berisi konten.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $raw = (string) $this->input('search', '');
            $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw) ?? '';
            $this->merge(['search' => trim($clean)]);
        }
    }

    // ─── Accessor helper (optional sugar untuk controller) ──────────────────
    public function page(): int
    {
        return max(1, (int) $this->input('page', 1));
    }

    public function perPage(): int
    {
        $value = (int) $this->input('per_page', 10);
        return max(1, min(self::PER_PAGE_MAX, $value));
    }

    public function sortBy(): string
    {
        $value = (string) $this->input('sort_by', 'created_at');
        return in_array($value, self::ALLOWED_SORT, true) ? $value : 'created_at';
    }

    public function sortDir(): string
    {
        return strtolower((string) $this->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
    }

    public function searchTerm(): string
    {
        return (string) $this->input('search', '');
    }
}
