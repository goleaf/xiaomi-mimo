<?php

namespace App\Actions;

use App\Models\Tag;

class UpdateTag
{
    public function handle(Tag $tag, string $name): Tag
    {
        $tag->update(['name' => $name]);

        return $tag->fresh();
    }
}
