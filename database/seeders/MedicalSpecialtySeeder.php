<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\MedicalSpecialty;

class MedicalSpecialtySeeder extends Seeder {
    public function run(): void {
        MedicalSpecialty::factory()->count(50)->create();
    }
}