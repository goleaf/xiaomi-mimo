<?php

namespace App\Http\Requests;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $todo = $this->route('todo');

        return $user instanceof User
            && $todo instanceof Todo
            && $user->can('view', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cursor' => ['nullable', 'string', 'max:2048'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }
}
