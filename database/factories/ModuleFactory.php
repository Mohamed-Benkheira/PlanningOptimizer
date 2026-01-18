<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    private static int $counter = 1000;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = ['Algorithmique', 'Analyse', 'Algèbre', 'Probabilités', 'Bases de Données', 'Réseaux', 'Systèmes', 'Droit', 'Gestion', 'Marketing', 'Physique', 'Chimie'];

        self::$counter++;

        return [
            'name' => $this->faker->randomElement($subjects) . ' ' . $this->faker->randomDigitNotNull,
            'code' => strtoupper($this->faker->lexify('???')) . self::$counter, // FIXED: counter instead of unique()
            'credits' => $this->faker->randomElement([2, 3, 4, 6]),
            'semester' => 1,
        ];
    }
}
