<?php

namespace App\Actions;

use App\Models\Tag;
use App\Models\Workspace;

class CreateTag
{
    public function handle(Workspace $workspace, string $name): Tag
    {
        return $workspace->tags()->create(['name' => $name]);
    }
}
