<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'logo_id' => null,
            'cover_image_id' => null,
            'website' => fake()->optional()->url(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'whatsapp' => null,
            'instagram' => null,
            'facebook' => null,
            'is_verified' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the company is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the company is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
