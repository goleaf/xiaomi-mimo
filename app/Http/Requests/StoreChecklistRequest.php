<?php

namespace App\Http\Requests;

use App\Models\Checklist;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $todo = $this->route('todo');
        $checklist = $this->route('checklist');

        if ($checklist instanceof Checklist) {
            $todo = $checklist->todo;
        }

        return $user instanceof User
            && $todo instanceof Todo
            && $user->can('update', $todo);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255']];
    }

    public function name(): string
    {
        return $this->string('name')->trim()->toString();
    }
}
