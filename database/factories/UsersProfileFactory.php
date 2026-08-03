<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\Nacionality;
use App\Models\User;
use App\Models\UsersProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsersProfile>
 */
class UsersProfileFactory extends Factory
{
    protected $model = UsersProfile::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName().' '.$this->faker->lastName();
        $dni = (string) $this->faker->unique()->numberBetween(1000000, 30000000);

        return [
            'photo_path' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'nacionality' => Nacionality::Venezuelan,
            'dni' => $dni,
            'birth_date' => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(Gender::values()),
            'phone_mobile' => '0'.(string) $this->faker->numberBetween(4120000000, 4149999999),
            'phone_landline' => $this->faker->boolean(40) ? '0'.(string) $this->faker->numberBetween(212000000, 212999999) : null,
            'created_by_user_id' => User::factory(),
            'user_id' => User::factory(),
        ];
    }
}
