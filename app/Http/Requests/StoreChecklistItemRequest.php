<?php

namespace App\Http\Requests;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $todo = $this->route('todo');
        $checklist = $this->route('checklist');
        $item = $this->route('item');

        if ($item instanceof ChecklistItem) {
            $checklist = $item->checklist;
        }

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
        return ['content' => ['required', 'string', 'max:500']];
    }

    public function content(): string
    {
        return $this->string('content')->trim()->toString();
    }
}
