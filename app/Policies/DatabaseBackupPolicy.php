<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Str;

class DatabaseBackupPolicy
{
    public function manage(User $user): bool
    {
        $operatorEmail = config('backup.operator_email');

        return is_string($operatorEmail)
            && $operatorEmail !== ''
            && hash_equals(Str::lower($operatorEmail), Str::lower($user->email))
            && $user->ownedWorkspaces()->exists();
    }
}
