<?php

namespace App\Actions;

use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class DeleteTag
{
    public function handle(Tag $tag): bool
    {
        return DB::transaction(function () use ($tag): bool {
            $tag->todos()->detach();

            return $tag->delete();
        }, 5);
    }
}
