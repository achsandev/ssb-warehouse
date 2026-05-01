<?php

declare(strict_types=1);

namespace App\Http\Requests\ApiClient;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateTokenRequest extends FormRequest
{
    /** Whitelist abilities yang boleh di-grant ke token API client. */
    public const ALLOWED_ABILITIES = [
        'items:read',
        'item-requests:create',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Kalau abilities tidak dikirim → default ke `*` (semua akses).
        // Kalau dikirim tapi bukan array → biarkan validator yang reject.
        if (! $this->has('abilities')) {
            $this->merge(['abilities' => ['*']]);
        }

        // Empty string `expires_at` → null (no expiration).
        if ($this->input('expires_at') === '') {
            $this->merge(['expires_at' => null]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            // Label internal supaya admin bisa membedakan token (mis. "v2-2026-04").
            'name'        => ['required', 'string', 'max:100'],

            // Kalau pakai wildcard, tidak boleh dicampur dengan ability spesifik.
            'abilities'   => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', 'in:*,'.implode(',', self::ALLOWED_ABILITIES)],

            // Optional — nullable artinya never expire.
            'expires_at'  => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string>
     */
    public function abilities(): array
    {
        $values = $this->input('abilities', []);
        if (! is_array($values)) {
            return ['*'];
        }

        // Dedup + filter empty.
        return array_values(array_unique(array_filter(array_map('strval', $values))));
    }

    public function expiresAt(): ?\DateTimeInterface
    {
        $value = $this->input('expires_at');
        if ($value === null || $value === '') {
            return null;
        }

        return new \DateTimeImmutable((string) $value);
    }
}
