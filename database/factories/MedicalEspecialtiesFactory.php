<?php

namespace Database\Factories;

use App\Models\MedicalEspecialties;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalEspecialties>
 */
class MedicalEspecialtiesFactory extends Factory
{
    protected $model = MedicalEspecialties::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Seed the model with a fixed set of 5 medical specialties.
     */
    public function seed(): static
    {
        return $this->sequence(
            ['name' => 'Cardiology', 'description' => 'Diagnosis and treatment of heart and cardiovascular system disorders.'],
            ['name' => 'Pediatrics', 'description' => 'Medical care of infants, children, and adolescents.'],
            ['name' => 'Dermatology', 'description' => 'Care of the skin, hair, and nails, including diagnosis of skin cancers.'],
            ['name' => 'Neurology', 'description' => 'Diagnosis and treatment of diseases of the nervous system.'],
            ['name' => 'General Medicine', 'description' => 'Comprehensive primary care for patients of all ages.'],
        );
    }
}
