<?php

declare(strict_types=1);

namespace App\Http\Requests\ApiClient;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization sudah di-handle middleware route (custom_permission).
        return true;
    }

    /**
     * Normalize input sebelum validasi:
     *   - boolean field di-cast ke true/null lewat FILTER_VALIDATE_BOOLEAN
     *     (anti misinterpretasi `"true"` / `"0"` / `"on"` dari form-urlencoded).
     *   - Empty string pada field optional → null (DB lebih bersih).
     */
    protected function prepareForValidation(): void
    {
        $merges = [];

        foreach (['is_active', 'enforce_origin'] as $bool) {
            if ($this->has($bool)) {
                $merges[$bool] = filter_var(
                    $this->input($bool),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                );
            }
        }

        if ($this->input('description') === '') {
            $merges['description'] = null;
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
            'name' => ['required', 'string', 'max:150', 'unique:api_clients,name'],
            // Wajib URL valid lengkap dengan scheme — tidak menerima
            // `partner.com` saja, harus `https://partner.com`.
            'url' => ['required', 'string', 'max:512', 'url'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'is_active'      => ['required', 'boolean'],
            'enforce_origin' => ['required', 'boolean'],
        ];
    }
}
