<?php

namespace App\Actions;

use App\Models\Tag;
use App\Models\Todo;
use Illuminate\Support\Facades\DB;

class SyncTodoTag
{
    public function attach(Todo $todo, Tag $tag): void
    {
        DB::transaction(
            fn () => $todo->tags()->syncWithoutDetachingOrFail([$tag->id]),
            5,
        );
    }

    public function detach(Todo $todo, Tag $tag): void
    {
        DB::transaction(
            fn () => $todo->tags()->detachOrFail($tag->id),
            5,
        );
    }
}
