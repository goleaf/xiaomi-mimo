<?php

namespace Database\Factories;

use App\Actions\EnsureWorkspaceTaskDefinitions;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Workspace $workspace): void {
            app(EnsureWorkspaceTaskDefinitions::class)->handle($workspace);
        });
    }

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'description' => fake()->sentence(),
            'owner_id' => UserFactory::new(),
        ];
    }
}
