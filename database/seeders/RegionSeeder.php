<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            [
                'name' => 'Central',
                'slug' => 'central',
                'description' => 'Home to Mount Kenya and the Aberdare Ranges, Central region offers dramatic highland trails through bamboo forests, moorlands, and glacier-fed valleys.',
                'latitude' => -0.72100000,
                'longitude' => 37.15200000,
                'sort_order' => 1,
            ],
            [
                'name' => 'Coast',
                'slug' => 'coast',
                'description' => 'Kenya\'s coastal region features trails through ancient forests like Arabuko-Sokoke, mangrove boardwalks, and the scenic Shimba Hills with ocean panoramas.',
                'latitude' => -3.94400000,
                'longitude' => 39.67100000,
                'sort_order' => 2,
            ],
            [
                'name' => 'Eastern',
                'slug' => 'eastern',
                'description' => 'Spanning from the slopes of Mount Kenya to the semi-arid lowlands, Eastern region includes trails in Meru National Park, Ol Donyo Sabuk, and the Chyulu Hills.',
                'latitude' => -0.45200000,
                'longitude' => 37.84900000,
                'sort_order' => 3,
            ],
            [
                'name' => 'Nairobi',
                'slug' => 'nairobi',
                'description' => 'Urban and peri-urban trails around Kenya\'s capital, including Karura Forest, Ngong Hills, Oloolua Nature Trail, and the Nairobi National Park boundary walks.',
                'latitude' => -1.28640000,
                'longitude' => 36.81720000,
                'sort_order' => 4,
            ],
            [
                'name' => 'North Eastern',
                'slug' => 'north-eastern',
                'description' => 'Kenya\'s frontier region with vast arid landscapes, seasonal river trails, and culturally rich routes through Garissa, Wajir, and Mandera counties.',
                'latitude' => 1.75000000,
                'longitude' => 40.06700000,
                'sort_order' => 5,
            ],
            [
                'name' => 'Nyanza',
                'slug' => 'nyanza',
                'description' => 'Trails around Lake Victoria, the Kericho tea highlands, and the rolling hills of Kisii. Includes routes through Ruma National Park and the Nandi Escarpment.',
                'latitude' => -0.67300000,
                'longitude' => 34.76700000,
                'sort_order' => 6,
            ],
            [
                'name' => 'Rift Valley',
                'slug' => 'rift-valley',
                'description' => 'Kenya\'s most diverse trail region, from the flamingo lakes of Nakuru and Bogoria to the Cherangani Hills, Mount Longonot, Hell\'s Gate, and the Kerio Valley escarpments.',
                'latitude' => 0.51400000,
                'longitude' => 36.06700000,
                'sort_order' => 7,
            ],
            [
                'name' => 'Western',
                'slug' => 'western',
                'description' => 'Home to the Kakamega Rainforest — Kenya\'s last tropical rainforest — plus Mount Elgon trails, the Nandi Hills, and scenic routes through verdant farmlands.',
                'latitude' => 0.57100000,
                'longitude' => 34.57200000,
                'sort_order' => 8,
            ],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['slug' => $region['slug']],
                $region,
            );
        }
    }
}
