<?php

namespace Database\Seeders;

use App\Models\Municipality;
use App\Models\State;
use Database\Factories\MunicipalityFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        $states = State::query()->orderBy('name')->get();

        foreach ($states as $state) {
            $municipalities = MunicipalityFactory::municipalitiesData()[$state->name] ?? [];

            foreach ($municipalities as $municipalityName) {
                Municipality::query()->updateOrCreate(
                    ['name' => $municipalityName, 'state_id' => $state->id],
                    [
                        'description' => 'Municipio '.$municipalityName.' del estado '.$state->name,
                        'value' => 'M-'.strtoupper(Str::slug($municipalityName, '-')),
                    ]
                );
            }
        }
    }
}
