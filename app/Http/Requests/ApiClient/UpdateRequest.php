<?php

declare(strict_types=1);

namespace App\Http\Requests\ApiClient;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
        $uid = $this->route('uid');

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('api_clients', 'name')->ignore($uid, 'uid'),
            ],
            'url' => ['required', 'string', 'max:512', 'url'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'is_active'      => ['required', 'boolean'],
            'enforce_origin' => ['required', 'boolean'],
        ];
    }
}
