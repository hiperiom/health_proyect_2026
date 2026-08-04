<?php

namespace Database\Seeders;

use App\Models\State;
use Database\Factories\StateFactory;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (StateFactory::statesData() as $state) {
            State::query()->updateOrCreate(
                ['name' => $state['name']],
                [
                    'description' => $state['description'],
                    'value' => $state['value'],
                ]
            );
        }
    }
}
