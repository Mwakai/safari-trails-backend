<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            'Camping',
            'Parking',
            'Restrooms',
            'Picnic Area',
            'Waterfall',
            'Scenic Viewpoint',
            'Wildlife Watching',
            'Bird Watching',
            'Swimming Hole',
            'River Crossing',
            'Rock Climbing',
            'Fishing',
            'Forest Trail',
            'Guide Available',
            'Photography Spot',
            'Hut/Banda Accommodation',
            'Water Source',
            'Firewood Available',
        ];

        foreach ($amenities as $name) {
            Amenity::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'is_active' => true,
                ]
            );
        }
    }
}
