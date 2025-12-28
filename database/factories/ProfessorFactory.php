<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Professor>
 */
class ProfessorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'grade' => $this->faker->randomElement(['MAA', 'MAB', 'MCA', 'MCB', 'Professeur']),
            'status' => 'active',
            // 'department_id' is passed by the Seeder, so we don't need it here
        ];
    }

}
