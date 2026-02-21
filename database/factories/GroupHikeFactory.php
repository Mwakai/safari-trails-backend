<?php

namespace Database\Factories;

use App\Enums\GroupHikeStatus;
use App\Enums\TrailDifficulty;
use App\Models\Company;
use App\Models\GroupHike;
use App\Models\Region;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GroupHike>
 */
class GroupHikeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(fake()->numberBetween(3, 5), true).' Hike';
        $startDate = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(15),
            'organizer_id' => User::factory(),
            'company_id' => null,
            'trail_id' => null,
            'custom_location_name' => fake()->city().' Trailhead',
            'latitude' => fake()->latitude(-4.5, 1.5),
            'longitude' => fake()->longitude(34, 41),
            'region_id' => Region::factory(),
            'meeting_point' => fake()->sentence(),
            'start_date' => $startDate->format('Y-m-d'),
            'start_time' => fake()->time('H:i:s', '08:00:00'),
            'end_date' => null,
            'end_time' => null,
            'max_participants' => fake()->optional()->numberBetween(5, 50),
            'registration_url' => fake()->optional()->url(),
            'registration_deadline' => null,
            'registration_notes' => null,
            'price' => null,
            'price_currency' => 'KES',
            'price_notes' => null,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->email(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_whatsapp' => null,
            'difficulty' => fake()->randomElement(TrailDifficulty::cases()),
            'featured_image_id' => null,
            'is_featured' => false,
            'is_recurring' => false,
            'recurring_notes' => null,
            'status' => GroupHikeStatus::Draft,
            'published_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'created_by' => fn (array $attrs) => $attrs['organizer_id'],
            'updated_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupHikeStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupHikeStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupHikeStatus::Completed,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupHikeStatus::Draft,
            'published_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function withTrail(Trail|int $trail): static
    {
        $trailId = $trail instanceof Trail ? $trail->id : $trail;

        return $this->state(fn (array $attributes) => [
            'trail_id' => $trailId,
            'custom_location_name' => null,
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function withCustomLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'trail_id' => null,
            'custom_location_name' => fake()->city().' Trailhead',
            'latitude' => fake()->latitude(-4.5, 1.5),
            'longitude' => fake()->longitude(34, 41),
        ]);
    }

    public function withCompany(Company|int $company): static
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $this->state(fn (array $attributes) => [
            'company_id' => $companyId,
        ]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
        ]);
    }

    public function multiDay(): static
    {
        $start = fake()->dateTimeBetween('+1 week', '+2 months');
        $end = fake()->dateTimeBetween($start, '+3 months');

        return $this->state(fn (array $attributes) => [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('+1 day', '+3 months')->format('Y-m-d'),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d'),
            'end_date' => null,
        ]);
    }
}
