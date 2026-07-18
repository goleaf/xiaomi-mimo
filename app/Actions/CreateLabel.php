<?php

namespace App\Actions;

use App\Models\Label;
use App\Models\Workspace;

class CreateLabel
{
    public function handle(Workspace $workspace, string $name, string $color = '#6366f1'): Label
    {
        return $workspace->labels()->create([
            'name' => $name,
            'color' => $color,
        ]);
    }
}
