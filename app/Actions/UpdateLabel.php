<?php

namespace App\Actions;

use App\Models\Label;

class UpdateLabel
{
    public function handle(Label $label, array $data): Label
    {
        $label->update(collect($data)->only(['name', 'color'])->toArray());

        return $label->fresh();
    }
}
