<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;

class UpdateUserPreferences
{
    /** @param array<string, bool|string> $attributes */
    public function execute(User $user, array $attributes): UserPreference
    {
        return DB::transaction(fn (): UserPreference => UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            $attributes,
        ));
    }
}
