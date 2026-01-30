<?php

namespace Database\Factories;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->slug(3);

        return [
            'filename' => $filename.'.jpg',
            'original_filename' => fake()->words(3, true).'.jpg',
            'path' => 'uploads/'.$filename.'.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(100000, 5000000),
            'type' => MediaType::Image,
            'width' => fake()->numberBetween(800, 3000),
            'height' => fake()->numberBetween(600, 2000),
            'duration' => null,
            'alt_text' => fake()->optional()->sentence(),
            'variants' => null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/jpeg',
            'type' => MediaType::Image,
            'width' => fake()->numberBetween(800, 3000),
            'height' => fake()->numberBetween(600, 2000),
        ]);
    }

    public function video(): static
    {
        $filename = fake()->slug(3);

        return $this->state(fn (array $attributes) => [
            'filename' => $filename.'.mp4',
            'original_filename' => fake()->words(3, true).'.mp4',
            'path' => 'uploads/'.$filename.'.mp4',
            'mime_type' => 'video/mp4',
            'type' => MediaType::Video,
            'width' => 1920,
            'height' => 1080,
            'duration' => fake()->numberBetween(10, 600),
        ]);
    }

    public function document(): static
    {
        $filename = fake()->slug(3);

        return $this->state(fn (array $attributes) => [
            'filename' => $filename.'.pdf',
            'original_filename' => fake()->words(3, true).'.pdf',
            'path' => 'uploads/'.$filename.'.pdf',
            'mime_type' => 'application/pdf',
            'type' => MediaType::Document,
            'width' => null,
            'height' => null,
        ]);
    }
}
