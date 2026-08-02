<?php
namespace Database\Factories;
use App\Models\MedicalSpecialty;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalSpecialtyFactory extends Factory {
    protected $model = MedicalSpecialty::class;
    public function definition(): array {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'value' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}