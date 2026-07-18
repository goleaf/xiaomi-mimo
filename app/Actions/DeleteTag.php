<?php

namespace App\Actions;

use App\Models\Tag;

class DeleteTag
{
    public function handle(Tag $tag): bool
    {
        return $tag->delete();
    }
}
