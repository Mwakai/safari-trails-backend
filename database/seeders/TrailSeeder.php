<?php

namespace Database\Seeders;

use App\Enums\DurationType;
use App\Models\Amenity;
use App\Models\Region;
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

        $regions = Region::all();

        if ($regions->isEmpty()) {
            return;
        }

        $amenities = Amenity::all();

        $trails = Trail::factory()
            ->count(5)
            ->create([
                'created_by' => $contentManager->id,
                'region_id' => fn () => $regions->random()->id,
            ]);

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
            ->create([
                'created_by' => $contentManager->id,
                'region_id' => fn () => $regions->random()->id,
            ]);

        // Multi-day trail with seasons and guide requirement
        $multiDay1 = Trail::factory()
            ->published()
            ->multiDay()
            ->withGuideRequired()
            ->withPermitRequired('KWS permit required - apply at park gate')
            ->withAccommodation(['camping', 'huts'])
            ->create([
                'name' => 'Mt Kenya Sirimon-Chogoria Traverse',
                'slug' => 'mt-kenya-sirimon-chogoria',
                'created_by' => $contentManager->id,
                'region_id' => $regions->random()->id,
                'duration_type' => DurationType::Days,
                'duration_min' => 4,
                'duration_max' => 5,
                'is_year_round' => false,
                'season_notes' => 'Best during dry seasons. Avoid heavy rains in April-May.',
            ]);
        $multiDay1->setBestMonths([1, 2, 3, 7, 8, 9, 10]);

        // Multi-day trail with accommodation
        $multiDay2 = Trail::factory()
            ->published()
            ->multiDay()
            ->withAccommodation(['camping', 'bandas', 'hotels'])
            ->create([
                'name' => 'Aberdare Ranges Circuit',
                'slug' => 'aberdare-ranges-circuit',
                'created_by' => $contentManager->id,
                'region_id' => $regions->random()->id,
                'duration_type' => DurationType::Days,
                'duration_min' => 3,
                'duration_max' => 4,
                'is_year_round' => false,
                'season_notes' => 'Heavy rainfall expected in April-May and November.',
            ]);
        $multiDay2->setBestMonths([1, 2, 6, 7, 8, 9, 12]);

        if ($amenities->isNotEmpty()) {
            $multiDay1->amenities()->attach(
                $amenities->random(min(3, $amenities->count()))->pluck('id')
            );
            $multiDay2->amenities()->attach(
                $amenities->random(min(2, $amenities->count()))->pluck('id')
            );
        }
    }
}
