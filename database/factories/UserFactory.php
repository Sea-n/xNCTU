<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'stuid' => $this->faker->unique()->regexify('0[5678][1568]\d{4}'),
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
        ];
    }
}
