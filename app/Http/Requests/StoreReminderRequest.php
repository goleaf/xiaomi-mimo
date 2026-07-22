<?php

namespace App\Http\Requests;

use App\Enums\ReminderType;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $todo = $this->route('todo');

        return $user instanceof User
            && $todo instanceof Todo
            && $user->can('update', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reminded_at' => ['required', 'date', 'after:now'],
            'type' => ['sometimes', 'string', Rule::enum(ReminderType::class)],
        ];
    }

    public function remindedAt(): string
    {
        return $this->string('reminded_at')->toString();
    }

    public function type(): string
    {
        return $this->string('type', ReminderType::InApp->value)->toString();
    }
}
