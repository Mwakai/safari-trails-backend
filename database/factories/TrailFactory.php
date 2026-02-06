<?php

namespace Database\Factories;

use App\Enums\TrailDifficulty;
use App\Enums\TrailStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trail>
 */
class TrailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(fake()->numberBetween(2, 4), true).' Trail';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(15),
            'difficulty' => fake()->randomElement(TrailDifficulty::cases()),
            'distance_km' => fake()->randomFloat(2, 1, 50),
            'duration_hours' => fake()->randomFloat(1, 0.5, 12),
            'elevation_gain_m' => fake()->optional()->numberBetween(50, 3000),
            'max_altitude_m' => fake()->optional()->numberBetween(1000, 5200),
            'latitude' => fake()->latitude(-4.5, 1.5),
            'longitude' => fake()->longitude(34, 41),
            'location_name' => fake()->city(),
            'county' => fake()->randomElement(['nairobi', 'kiambu', 'nakuru', 'kajiado', 'nyeri', 'laikipia']),
            'route_a_name' => fake()->optional()->words(3, true),
            'route_a_description' => fake()->optional()->paragraph(),
            'route_a_latitude' => fake()->optional()->latitude(-4.5, 1.5),
            'route_a_longitude' => fake()->optional()->longitude(34, 41),
            'route_b_enabled' => false,
            'route_b_name' => null,
            'route_b_description' => null,
            'route_b_latitude' => null,
            'route_b_longitude' => null,
            'featured_image_id' => null,
            'video_url' => null,
            'status' => TrailStatus::Draft,
            'published_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrailStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrailStatus::Archived,
        ]);
    }

    public function withDifficulty(TrailDifficulty $difficulty): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => $difficulty,
        ]);
    }

    public function withCounty(string $county): static
    {
        return $this->state(fn (array $attributes) => [
            'county' => $county,
        ]);
    }

    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    public function withRouteB(): static
    {
        return $this->state(fn (array $attributes) => [
            'route_b_enabled' => true,
            'route_b_name' => fake()->words(3, true),
            'route_b_description' => fake()->paragraph(),
            'route_b_latitude' => fake()->latitude(-4.5, 1.5),
            'route_b_longitude' => fake()->longitude(34, 41),
        ]);
    }
}
