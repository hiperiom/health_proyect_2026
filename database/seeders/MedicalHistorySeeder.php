<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\MedicalHistory;

class MedicalHistorySeeder extends Seeder {
    public function run(): void {
        MedicalHistory::factory()->count(50)->create();
    }
}