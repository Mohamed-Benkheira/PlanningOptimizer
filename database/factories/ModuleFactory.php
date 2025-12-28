<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = ['Algorithmique', 'Analyse', 'Algèbre', 'Probabilités', 'Bases de Données', 'Réseaux', 'Systèmes', 'Droit', 'Gestion', 'Marketing', 'Physique', 'Chimie'];

        return [
            'name' => $this->faker->randomElement($subjects) . ' ' . $this->faker->randomDigitNotNull,
            'code' => strtoupper($this->faker->lexify('???')) . $this->faker->unique()->numberBetween(100, 999),
            'credits' => $this->faker->randomElement([2, 3, 4, 6]),
            'semester' => 1, // Default, usually overridden by Seeder
            // specialty_id and level_id passed by Seeder
        ];
    }

}
