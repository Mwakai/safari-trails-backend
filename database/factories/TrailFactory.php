<?php

namespace Database\Factories;

use App\Enums\DurationType;
use App\Enums\TrailDifficulty;
use App\Enums\TrailStatus;
use App\Models\Region;
use App\Models\TrailItineraryDay;
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
            'duration_type' => DurationType::Hours,
            'duration_min' => fake()->randomFloat(1, 0.5, 12),
            'duration_max' => null,
            'elevation_gain_m' => fake()->optional()->numberBetween(50, 3000),
            'max_altitude_m' => fake()->optional()->numberBetween(1000, 5200),
            'is_year_round' => true,
            'season_notes' => null,
            'requires_guide' => false,
            'requires_permit' => false,
            'permit_info' => null,
            'accommodation_types' => null,
            'latitude' => fake()->latitude(-4.5, 1.5),
            'longitude' => fake()->longitude(34, 41),
            'location_name' => fake()->randomElement([
                'Nairobi', 'Naivasha', 'Nakuru', 'Nanyuki', 'Nyeri', 'Meru',
                'Kisumu', 'Eldoret', 'Kitale', 'Kakamega', 'Mombasa', 'Malindi',
                'Machakos', 'Kajiado', 'Embu', 'Thika', 'Limuru', 'Kericho',
                'Kisii', 'Homa Bay', 'Bungoma', 'Webuye', 'Athi River',
            ]),
            'region_id' => Region::factory(),
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

    public function withRegion(Region|int $region): static
    {
        return $this->state(fn (array $attributes) => [
            'region_id' => $region instanceof Region ? $region->id : $region,
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

    public function multiDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => DurationType::Days,
            'duration_min' => fake()->numberBetween(2, 5),
            'duration_max' => fake()->numberBetween(5, 10),
        ]);
    }

    public function withDuration(DurationType $type, float $min, ?float $max = null): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => $type,
            'duration_min' => $min,
            'duration_max' => $max,
        ]);
    }

    /**
     * @param  array<int>  $months
     */
    public function seasonal(array $months): static
    {
        return $this->state(fn (array $attributes) => [
            'is_year_round' => false,
        ])->afterCreating(function (\App\Models\Trail $trail) use ($months) {
            $trail->setBestMonths($months);
        });
    }

    public function withGuideRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_guide' => true,
        ]);
    }

    public function withPermitRequired(?string $info = null): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_permit' => true,
            'permit_info' => $info ?? 'Permit required from KWS',
        ]);
    }

    /**
     * @param  array<string>  $types
     */
    public function withAccommodation(array $types): static
    {
        return $this->state(fn (array $attributes) => [
            'accommodation_types' => $types,
        ]);
    }

    public function withItinerary(int $days = 3): static
    {
        return $this->multiDay()->afterCreating(function (\App\Models\Trail $trail) use ($days) {
            for ($i = 1; $i <= $days; $i++) {
                TrailItineraryDay::factory()->forDay($i)->create(['trail_id' => $trail->id]);
            }
        });
    }
}
