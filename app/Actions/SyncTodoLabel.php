<?php

namespace App\Actions;

use App\Models\Label;
use App\Models\Todo;
use Illuminate\Support\Facades\DB;

class SyncTodoLabel
{
    public function attach(Todo $todo, Label $label): void
    {
        DB::transaction(
            fn () => $todo->labels()->syncWithoutDetachingOrFail([$label->id]),
            5,
        );
    }

    public function detach(Todo $todo, Label $label): void
    {
        DB::transaction(
            fn () => $todo->labels()->detachOrFail($label->id),
            5,
        );
    }
}
