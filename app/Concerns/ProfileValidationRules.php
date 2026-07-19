<?php

namespace App\Concerns;

use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /** @return array<string, mixed> */
    protected function profileRules(?string $userId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255',
                $userId ? Rule::unique('users')->ignore($userId) : Rule::unique('users'),
            ],
        ];
    }
}
