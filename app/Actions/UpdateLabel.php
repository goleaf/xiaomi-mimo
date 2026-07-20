<?php

namespace App\Actions;

use App\Models\Label;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class UpdateLabel
{
    /** @param array{name?: string, color?: string} $data */
    public function handle(Label $label, array $data): Label
    {
        try {
            $label->update(collect($data)->only(['name', 'color'])->toArray());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => [__('validation.unique', ['attribute' => 'name'])],
            ]);
        }

        return $label->refresh()->load(['workspace'])->loadCount('todos');
    }
}
