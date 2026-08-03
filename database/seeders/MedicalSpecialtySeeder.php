<?php

namespace Database\Seeders;

use App\Models\MedicalSpecialty;
use Illuminate\Database\Seeder;

class MedicalSpecialtySeeder extends Seeder
{
    public function run(): void
    {
        MedicalSpecialty::factory()->count(50)->create();
    }
}
