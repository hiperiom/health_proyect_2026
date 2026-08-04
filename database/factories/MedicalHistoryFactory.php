<?php
namespace Database\Factories;
use App\Models\MedicalHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalHistoryFactory extends Factory {
    protected $model = MedicalHistory::class;
    public function definition(): array {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'value' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}