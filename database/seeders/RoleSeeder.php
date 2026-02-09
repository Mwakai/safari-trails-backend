<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Full system access with all permissions',
                'permissions' => ['*'],
                'is_system' => true,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access with most permissions',
                'permissions' => [
                    'users.view',
                    'users.create',
                    'users.update',
                    'users.delete',
                    'companies.*',
                    'media.*',
                    'trails.*',
                    'amenities.*',
                    'group_hikes.*',
                    'activity_logs.view',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Content Manager',
                'slug' => 'content_manager',
                'description' => 'Manage media, trails, and amenities content',
                'permissions' => [
                    'media.*',
                    'trails.*',
                    'amenities.*',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Group Hike Organizer',
                'slug' => 'group_hike_organizer',
                'description' => 'Organize and manage group hike events',
                'permissions' => [
                    'media.view',
                    'group_hikes.view_own',
                    'group_hikes.create',
                    'group_hikes.update_own',
                    'group_hikes.delete_own',
                ],
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
