<?php

namespace App\Actions;

use App\Models\Checklist;

class ManageChecklist
{
    public function update(Checklist $checklist, string $name): Checklist
    {
        $checklist->update(['name' => $name]);

        return $checklist->refresh()->load('items');
    }

    public function delete(Checklist $checklist): bool
    {
        return (bool) $checklist->delete();
    }
}
