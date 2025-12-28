<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['Salle', 'Amphi', 'Laboratoire']);
        $capacity = match ($type) {
            'Amphi' => $this->faker->numberBetween(150, 300),
            'Salle' => $this->faker->numberBetween(20, 40),
            'Laboratoire' => 20,
        };

        return [
            'name' => $type . ' ' . $this->faker->unique()->numberBetween(1, 100),
            'code' => strtoupper(substr($type, 0, 1)) . $this->faker->unique()->numberBetween(100, 999),
            'type' => $type,
            'capacity' => $capacity,
            'building' => $this->faker->randomElement(['Bloc A', 'Bloc B', 'Bloc C']),
            'is_active' => true,
        ];
    }

}
