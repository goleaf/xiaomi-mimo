<?php

namespace App\Actions;

use App\Models\Label;

class DeleteLabel
{
    public function handle(Label $label): bool
    {
        return $label->delete();
    }
}
