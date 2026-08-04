<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            ...fake()->randomElement(static::statesData()),
            'is_active' => fake()->boolean(),
        ];
    }

    /**
     * Los 24 estados de Venezuela (23 estados + Distrito Capital)
     * en orden alfabético, con su código ISO 3166-2:VE.
     *
     * @return array<int, array{name: string, description: string, value: string}>
     */
    public static function statesData(): array
    {
        return [
            ['name' => 'Amazonas', 'description' => 'Estado Amazonas de Venezuela', 'value' => 'VE-Z'],
            ['name' => 'Anzoátegui', 'description' => 'Estado Anzoátegui de Venezuela', 'value' => 'VE-B'],
            ['name' => 'Apure', 'description' => 'Estado Apure de Venezuela', 'value' => 'VE-C'],
            ['name' => 'Aragua', 'description' => 'Estado Aragua de Venezuela', 'value' => 'VE-D'],
            ['name' => 'Barinas', 'description' => 'Estado Barinas de Venezuela', 'value' => 'VE-E'],
            ['name' => 'Bolívar', 'description' => 'Estado Bolívar de Venezuela', 'value' => 'VE-F'],
            ['name' => 'Carabobo', 'description' => 'Estado Carabobo de Venezuela', 'value' => 'VE-G'],
            ['name' => 'Cojedes', 'description' => 'Estado Cojedes de Venezuela', 'value' => 'VE-H'],
            ['name' => 'Delta Amacuro', 'description' => 'Estado Delta Amacuro de Venezuela', 'value' => 'VE-Y'],
            ['name' => 'Distrito Capital', 'description' => 'Distrito Capital de Venezuela', 'value' => 'VE-A'],
            ['name' => 'Falcón', 'description' => 'Estado Falcón de Venezuela', 'value' => 'VE-I'],
            ['name' => 'Guárico', 'description' => 'Estado Guárico de Venezuela', 'value' => 'VE-J'],
            ['name' => 'La Guaira', 'description' => 'Estado La Guaira de Venezuela', 'value' => 'VE-X'],
            ['name' => 'Lara', 'description' => 'Estado Lara de Venezuela', 'value' => 'VE-K'],
            ['name' => 'Mérida', 'description' => 'Estado Mérida de Venezuela', 'value' => 'VE-L'],
            ['name' => 'Miranda', 'description' => 'Estado Miranda de Venezuela', 'value' => 'VE-M'],
            ['name' => 'Monagas', 'description' => 'Estado Monagas de Venezuela', 'value' => 'VE-N'],
            ['name' => 'Nueva Esparta', 'description' => 'Estado Nueva Esparta de Venezuela', 'value' => 'VE-O'],
            ['name' => 'Portuguesa', 'description' => 'Estado Portuguesa de Venezuela', 'value' => 'VE-P'],
            ['name' => 'Sucre', 'description' => 'Estado Sucre de Venezuela', 'value' => 'VE-R'],
            ['name' => 'Táchira', 'description' => 'Estado Táchira de Venezuela', 'value' => 'VE-S'],
            ['name' => 'Trujillo', 'description' => 'Estado Trujillo de Venezuela', 'value' => 'VE-T'],
            ['name' => 'Yaracuy', 'description' => 'Estado Yaracuy de Venezuela', 'value' => 'VE-U'],
            ['name' => 'Zulia', 'description' => 'Estado Zulia de Venezuela', 'value' => 'VE-V'],
        ];
    }
}
