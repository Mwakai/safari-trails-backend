<?php

namespace Database\Factories;

use App\Models\Trail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrailItineraryDay>
 */
class TrailItineraryDayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trail_id' => Trail::factory(),
            'day_number' => 1,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'distance_km' => fake()->optional()->randomFloat(2, 2, 30),
            'elevation_gain_m' => fake()->optional()->numberBetween(100, 1500),
            'start_point' => fake()->optional()->city(),
            'end_point' => fake()->optional()->city(),
            'accommodation' => fake()->optional()->randomElement(['Camping', 'Mountain hut', 'Bandas', 'Lodge']),
            'sort_order' => 0,
        ];
    }

    public function forDay(int $dayNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'day_number' => $dayNumber,
            'title' => "Day {$dayNumber}: ".fake()->sentence(3),
            'sort_order' => $dayNumber - 1,
        ]);
    }
}
