<?php

namespace App\Concerns;

use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    protected function profileRules(string $userId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
        ];
    }
}
