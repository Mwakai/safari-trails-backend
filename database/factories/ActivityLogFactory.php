<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'log_name' => fake()->randomElement(['trails', 'users', 'amenities', 'media']),
            'event' => fake()->randomElement(['created', 'updated', 'deleted', 'status_changed']),
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => null,
            'created_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }

    public function forSubject(Model $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    public function causedBy(Model $causer): static
    {
        return $this->state(fn (array $attributes) => [
            'causer_type' => $causer->getMorphClass(),
            'causer_id' => $causer->getKey(),
        ]);
    }

    public function withLogName(string $logName): static
    {
        return $this->state(fn (array $attributes) => [
            'log_name' => $logName,
        ]);
    }

    public function withEvent(string $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => $event,
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function withProperties(array $properties): static
    {
        return $this->state(fn (array $attributes) => [
            'properties' => $properties,
        ]);
    }
}
