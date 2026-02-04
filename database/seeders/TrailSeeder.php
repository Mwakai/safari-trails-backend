<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contentManager = User::whereHas('role', function ($q) {
            $q->where('slug', 'content_manager');
        })->first();

        if (! $contentManager) {
            return;
        }

        $amenities = Amenity::all();

        $trails = Trail::factory()
            ->count(5)
            ->create(['created_by' => $contentManager->id]);

        foreach ($trails as $trail) {
            if ($amenities->isNotEmpty()) {
                $trail->amenities()->attach(
                    $amenities->random(min(3, $amenities->count()))->pluck('id')
                );
            }
        }

        Trail::factory()
            ->count(3)
            ->published()
            ->create(['created_by' => $contentManager->id]);
    }
}
