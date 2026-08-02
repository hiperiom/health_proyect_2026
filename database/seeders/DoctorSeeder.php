<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Doctor;

class DoctorSeeder extends Seeder {
    public function run(): void {
        Doctor::factory()->count(50)->create();
    }
}