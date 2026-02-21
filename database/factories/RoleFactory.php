<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name, '_'),
            'description' => fake()->sentence(),
            'permissions' => [],
            'is_system' => false,
        ];
    }

    /**
     * Create a super admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'description' => 'Full system access with all permissions',
            'permissions' => ['*'],
            'is_system' => true,
        ]);
    }

    /**
     * Create an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrative access with most permissions',
            'permissions' => [
                'users.*',
                'companies.*',
                'media.*',
                'trails.*',
                'amenities.*',
                'group_hikes.*',
                'activity_logs.view',
            ],
            'is_system' => true,
        ]);
    }

    /**
     * Create a content manager role.
     */
    public function contentManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Content Manager',
            'slug' => 'content_manager',
            'description' => 'Manage media, trails, and amenities content',
            'permissions' => [
                'media.*',
                'trails.*',
                'amenities.*',
            ],
            'is_system' => true,
        ]);
    }

    /**
     * Create a group hike organizer role.
     */
    public function groupHikeOrganizer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Group Hike Organizer',
            'slug' => 'group_hike_organizer',
            'description' => 'Organize and manage group hike events',
            'permissions' => [
                'media.view',
                'companies.view',
                'group_hikes.view',
                'group_hikes.create',
                'group_hikes.update',
                'group_hikes.delete',
            ],
            'is_system' => true,
        ]);
    }
}
