<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            RegionSeeder::class,
        ]);

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $contentManagerRole = Role::where('slug', 'content_manager')->first();
        $organizerRole = Role::where('slug', 'group_hike_organizer')->first();

        // Default super admin
        User::factory()->create([
            'role_id' => $superAdminRole->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@kenyatrails.com',
            'password' => 'KenyaTrails!123',
        ]);

        // 2 additional admins
        User::factory()->count(2)->create([
            'role_id' => $adminRole->id,
        ]);

        // 3 content managers
        User::factory()->count(3)->create([
            'role_id' => $contentManagerRole->id,
        ]);

        // 4 group hike organizers
        User::factory()->count(4)->create([
            'role_id' => $organizerRole->id,
        ]);

        $this->call([
            AmenitySeeder::class,
            TrailSeeder::class,
            TrailMediaSeeder::class,
        ]);
    }
}
