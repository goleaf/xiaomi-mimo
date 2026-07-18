<?php

namespace Database\Factories;

use App\Enums\WorkspaceRole;
use App\Models\UserFactory;
use App\Models\WorkspaceFactory;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkspaceMemberFactory extends Factory
{
    protected $model = WorkspaceMember::class;

    public function definition(): array
    {
        return [
            'workspace_id' => WorkspaceFactory::new(),
            'user_id' => UserFactory::new(),
            'role' => WorkspaceRole::Member,
        ];
    }
}
