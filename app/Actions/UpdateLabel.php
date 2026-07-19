<?php

namespace App\Actions;

use App\Models\Label;

class UpdateLabel
{
    /** @param array{name?: string, color?: string} $data */
    public function handle(Label $label, array $data): Label
    {
        $label->update(collect($data)->only(['name', 'color'])->toArray());

        return $label->fresh();
    }
}
