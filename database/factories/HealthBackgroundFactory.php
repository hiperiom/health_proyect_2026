<?php

namespace Database\Factories;

use App\Models\HealthBackground;
use Illuminate\Database\Eloquent\Factories\Factory;

class HealthBackgroundFactory extends Factory
{
    protected $model = HealthBackground::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'value' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
