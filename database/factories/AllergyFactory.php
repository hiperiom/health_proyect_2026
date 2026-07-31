<?php

namespace Database\Factories;

use App\Models\Allergy;
use Illuminate\Database\Eloquent\Factories\Factory;

class AllergyFactory extends Factory
{
    protected $model = Allergy::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'value' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
