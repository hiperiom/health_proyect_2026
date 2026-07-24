<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());
        $module = fake()->randomElement(['users', 'roles', 'permissions', 'modules']);

        return [
            'name' => $name,
            'slug' => $module.'.'.Str::slug(fake()->word()),
            'module' => $module,
            'description' => fake()->sentence(),
        ];
    }

    public function forModule(string $module, string $action = 'read'): static
    {
        return $this->state(fn (): array => [
            'name' => ucfirst($action),
            'slug' => $module.'.'.$action,
            'module' => $module,
            'description' => ucfirst($action).' '.$module.' records',
        ]);
    }
}
