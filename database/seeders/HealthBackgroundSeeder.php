<?php

namespace Database\Seeders;

use App\Models\HealthBackground;
use Illuminate\Database\Seeder;

class HealthBackgroundSeeder extends Seeder
{
    public function run(): void
    {
        HealthBackground::factory()->count(50)->create();
    }
}
