<?php

namespace App\Actions;

use App\Models\Label;
use Illuminate\Support\Facades\DB;

class DeleteLabel
{
    public function handle(Label $label): bool
    {
        return DB::transaction(function () use ($label): bool {
            $label->todos()->detach();

            return $label->delete();
        }, 5);
    }
}
