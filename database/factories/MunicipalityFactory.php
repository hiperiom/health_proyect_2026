<?php

namespace Database\Factories;

use App\Models\Municipality;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MunicipalityFactory extends Factory
{
    protected $model = Municipality::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->sentence(),
            'value' => (string) $this->faker->randomFloat(2, 10, 1000),
            'state_id' => State::query()->inRandomOrder()->value('id'),
            'is_active' => $this->faker->boolean(),
        ];
    }

    /**
     * Todos los municipios de Venezuela agrupados por estado.
     *
     * @return array<string, array<int, string>>
     */
    public static function municipalitiesData(): array
    {
        $path = database_path('seeders/data/municipalities.php');

        if (! file_exists($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data) ? $data : [];
    }

    /**
     * El municipio se crea vinculado al estado que le corresponde.
     */
    public function forState(State $state): static
    {
        $municipalities = static::municipalitiesData()[$state->name] ?? [];

        if ($municipalities === []) {
            return $this->state(['state_id' => $state->id]);
        }

        $count = DB::table('municipalities')->where('state_id', $state->id)->count();
        $name = $municipalities[$count % count($municipalities)];

        return $this->state([
            'name' => $name,
            'description' => 'Municipio '.$name.' del estado '.$state->name,
            'value' => 'M-'.strtoupper(Str::slug($name, '-')),
            'state_id' => $state->id,
        ]);
    }
}
