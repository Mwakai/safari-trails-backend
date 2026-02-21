<?php

namespace Database\Seeders;

use App\Enums\DurationType;
use App\Enums\TrailDifficulty;
use App\Enums\TrailStatus;
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

        $amenities = Amenity::all()->keyBy('slug');

        $amenityId = fn (string $slug) => $amenities->get($slug)?->id;

        $trails = [
            // ── NAIROBI ───────────────────────────────────────────────────────
            [
                'name' => "Hell's Gate Gorge Walk",
                'slug' => 'hells-gate-gorge-walk',
                'short_description' => 'Walk or cycle through dramatic volcanic gorges alongside zebra, giraffe, and buffalo in this unique park where wildlife roams freely among hikers.',
                'description' => "Hell's Gate National Park offers one of Kenya's most unique hiking experiences — you walk (or cycle) through the park without a vehicle, alongside wildlife. The gorge trail winds through towering red cliffs sculpted by geothermal activity, past Fischer's Tower and the Central Tower, into the Inner Gorge where hot springs bubble at the base of narrow canyon walls.\n\nThe main gorge descends steeply and requires scrambling through the narrow passage — a slot canyon experience unlike anything else in Kenya. Buffalo, zebra, giraffe, and warthog are commonly encountered on the open plateau above.",
                'difficulty' => TrailDifficulty::Moderate,
                'distance_km' => 22.0,
                'duration_type' => DurationType::Hours,
                'duration_min' => 5.0,
                'duration_max' => 7.0,
                'elevation_gain_m' => 180,
                'max_altitude_m' => 1910,
                'latitude' => -0.9088,
                'longitude' => 36.3139,
                'location_name' => 'Naivasha',
                'region_slug' => 'rift-valley',
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['wildlife-watching', 'bird-watching', 'parking', 'photography-spot', 'scenic-viewpoint'],
            ],
            [
                'name' => 'Ngong Hills Ridgeline Trail',
                'slug' => 'ngong-hills-ridgeline-trail',
                'short_description' => 'Hike the iconic ridgeline above Nairobi with sweeping views of the Great Rift Valley and the city skyline — a classic escape from the capital.',
                'description' => "The Ngong Hills form a dramatic escarpment on the edge of the Great Rift Valley, just 25 km southwest of Nairobi. The ridgeline trail traverses all eight peaks — the highest reaching 2,460 m — offering panoramic views in both directions: the Rift Valley dropping away to the west, and Nairobi's skyline glittering to the east on clear mornings.\n\nLion and buffalo have historically been present; a Kenya Wildlife Service ranger escort is recommended (and sometimes mandatory). Wind farms dot the upper ridge, adding a surreal industrial contrast to the wild moorland.",
                'difficulty' => TrailDifficulty::Moderate,
                'distance_km' => 16.5,
                'duration_type' => DurationType::Hours,
                'duration_min' => 5.0,
                'duration_max' => 7.0,
                'elevation_gain_m' => 620,
                'max_altitude_m' => 2460,
                'latitude' => -1.3667,
                'longitude' => 36.6500,
                'location_name' => 'Ngong',
                'region_slug' => 'nairobi',
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['scenic-viewpoint', 'photography-spot', 'parking', 'guide-available'],
            ],
            [
                'name' => 'Karura Forest Loop',
                'slug' => 'karura-forest-loop',
                'short_description' => "Nairobi's urban forest sanctuary offers peaceful trails through indigenous woodland, past waterfalls and caves — a remarkable escape within the city.",
                'description' => "Karura Forest is a 1,041-hectare indigenous forest reserve sitting within Nairobi — one of the largest urban forests in the world. The trail network winds through Mau Mau caves, past two beautiful waterfalls (Mamba Falls and the Butterfly Falls area), and along the Karura River through dense Croton-Calodendrum woodland.\n\nMonkey families, bushbuck, and over 200 bird species inhabit the forest. The well-maintained paths are excellent for running, cycling, or a gentle family walk. Security is good, with rangers posted throughout.",
                'difficulty' => TrailDifficulty::Easy,
                'distance_km' => 8.0,
                'duration_type' => DurationType::Hours,
                'duration_min' => 2.0,
                'duration_max' => 3.0,
                'elevation_gain_m' => 80,
                'max_altitude_m' => 1720,
                'latitude' => -1.2318,
                'longitude' => 36.8247,
                'location_name' => 'Nairobi',
                'region_slug' => 'nairobi',
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['parking', 'restrooms', 'waterfall', 'bird-watching', 'forest-trail', 'picnic-area'],
            ],
            [
                'name' => 'Oloolua Nature Trail',
                'slug' => 'oloolua-nature-trail',
                'short_description' => 'A tranquil forest trail in Karen with a beautiful waterfall, Mau Mau caves, and resident colobus monkeys — ideal for families and casual hikers.',
                'description' => "Oloolua Forest is a small but beautiful indigenous forest near Karen, offering a peaceful escape from Nairobi. The trail follows the Mbagathi River gorge through dense Croton and Olea woodland, descending to a picturesque waterfall and passing through historic Mau Mau caves used during Kenya's independence struggle.\n\nBlack-and-white colobus monkeys are frequently seen in the canopy. The forest is one of the last remnants of the forest that once covered the Nairobi plains. Entry is managed by the Kenya Forest Service.",
                'difficulty' => TrailDifficulty::Easy,
                'distance_km' => 5.5,
                'duration_type' => DurationType::Hours,
                'duration_min' => 1.5,
                'duration_max' => 2.5,
                'elevation_gain_m' => 60,
                'max_altitude_m' => 1680,
                'latitude' => -1.3404,
                'longitude' => 36.7277,
                'location_name' => 'Karen, Nairobi',
                'region_slug' => 'nairobi',
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['waterfall', 'forest-trail', 'bird-watching', 'parking'],
            ],

            // ── RIFT VALLEY ────────────────────────────────────────────────────
            [
                'name' => 'Mt Longonot Crater Circuit',
                'slug' => 'mt-longonot-crater-circuit',
                'short_description' => 'Summit a young stratovolcano and circumnavigate its dramatic crater rim for breathtaking views over Lake Naivasha and the Great Rift Valley floor.',
                'description' => "Mount Longonot is a young stratovolcano rising to 2,776 m on the floor of the Great Rift Valley, about 60 km northwest of Nairobi. The hike climbs steeply through scrubby acacias to the crater rim, then follows the rim in a full circuit — a challenging 7 km loop with multiple false summits.\n\nThe views from the rim are extraordinary: Lake Naivasha gleams 700 m below, Hell's Gate cliffs are visible to the south, and on clear days you can see across the Rift Valley into the Mau Escarpment. Wildlife including buffalo and leopard inhabit the steep crater walls. A KWS permit and ranger escort are required.",
                'difficulty' => TrailDifficulty::Difficult,
                'distance_km' => 13.0,
                'duration_type' => DurationType::Hours,
                'duration_min' => 4.0,
                'duration_max' => 6.0,
                'elevation_gain_m' => 780,
                'max_altitude_m' => 2776,
                'latitude' => -0.9148,
                'longitude' => 36.4619,
                'location_name' => 'Naivasha',
                'region_slug' => 'rift-valley',
                'requires_permit' => true,
                'permit_info' => 'KWS park entry fee required. Ranger escort mandatory for crater rim circuit.',
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['scenic-viewpoint', 'photography-spot', 'parking', 'guide-available', 'wildlife-watching'],
            ],
            [
                'name' => 'Menengai Crater Walk',
                'slug' => 'menengai-crater-walk',
                'short_description' => 'Explore the rim of one of the world\'s largest calderas — a vast volcanic depression with geothermal vents, endemic forest, and commanding views over Nakuru.',
                'description' => "Menengai is the world's third-largest caldera by area, a 12 km wide volcanic depression rising to 2,278 m above sea level on the edge of Nakuru. The rim trail traverses indigenous forest draped in mist, passing geothermal steam vents still active today.\n\nThe caldera floor sits 500 m below the rim, with the Nakuru town visible on one side and Lake Nakuru (famous for its flamingos) on the other. The area is steeped in Maasai history — the caldera was the site of a decisive 19th-century battle. Colobus monkeys and leopards inhabit the forest.",
                'difficulty' => TrailDifficulty::Moderate,
                'distance_km' => 10.0,
                'duration_type' => DurationType::Hours,
                'duration_min' => 3.0,
                'duration_max' => 5.0,
                'elevation_gain_m' => 350,
                'max_altitude_m' => 2278,
                'latitude' => -0.2167,
                'longitude' => 36.0667,
                'location_name' => 'Nakuru',
                'region_slug' => 'rift-valley',
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['scenic-viewpoint', 'forest-trail', 'bird-watching', 'photography-spot'],
            ],

            // ── CENTRAL ───────────────────────────────────────────────────────
            [
                'name' => 'Mt Kenya Sirimon-Chogoria Traverse',
                'slug' => 'mt-kenya-sirimon-chogoria',
                'short_description' => 'Kenya\'s premier high-altitude trek crosses the equatorial snow-capped peaks of Africa\'s second-highest mountain, passing glacial tarns, giant groundsels, and dramatic ridgelines.',
                'description' => "The Sirimon-Chogoria traverse is widely considered the finest route on Mount Kenya. Ascending via the Sirimon track through the montane forest and moorland zones, the trail crosses the high plateau past Liki North Hut and Shipton's Camp before tackling the technical routes to Point Lenana (4,985 m) — the trekkers' summit.\n\nThe descent via Chogoria passes the stunning Hall Tarns, the vast crater lake of Lake Michaelson, and the mythical Gorges Valley before dropping through some of Kenya's most pristine Afro-alpine vegetation. The traverse takes 4–5 days and requires good acclimatisation. A KWS permit and guide are mandatory.",
                'difficulty' => TrailDifficulty::Difficult,
                'distance_km' => 68.0,
                'duration_type' => DurationType::Days,
                'duration_min' => 4,
                'duration_max' => 5,
                'elevation_gain_m' => 3500,
                'max_altitude_m' => 4985,
                'latitude' => -0.1521,
                'longitude' => 37.3084,
                'location_name' => 'Nanyuki',
                'region_slug' => 'central',
                'requires_guide' => true,
                'requires_permit' => true,
                'permit_info' => 'KWS park entry permit required. Guides mandatory above Mackinder\'s Camp. Apply through licensed Mt Kenya guides associations.',
                'is_year_round' => false,
                'season_notes' => 'Best during the two dry seasons: January–February and July–October. Avoid the long rains (April–June) and short rains (November–December).',
                'accommodation_types' => ['camping', 'huts'],
                'status' => TrailStatus::Published,
                'best_months' => [1, 2, 7, 8, 9, 10],
                'amenity_slugs' => ['camping', 'hut-banda-accommodation', 'guide-available', 'water-source', 'photography-spot', 'scenic-viewpoint'],
            ],
            [
                'name' => 'Aberdare Ranges Circuit',
                'slug' => 'aberdare-ranges-circuit',
                'short_description' => 'A multi-day traverse of the misty Aberdare moorlands, past roaring waterfalls, through bamboo forests, and across open heath roamed by elephant and buffalo.',
                'description' => "The Aberdare Range forms a 160 km moorland plateau at the heart of Kenya, rising to 4,001 m at Ol Donyo Lesatima. The circuit trail crosses the open montane moorland, descends through thick bamboo belts and Hagenia-Hypericum forest, and passes several spectacular waterfalls including the Karuru Falls (273 m — one of Kenya's highest).\n\nThe Aberdares are true wilderness: elephant, buffalo, leopard, lion, and the rare bongo antelope inhabit the park. Rain and mist are common year-round, making navigation challenging — a guide is strongly recommended. Several huts and camping sites are available along the route.",
                'difficulty' => TrailDifficulty::Difficult,
                'distance_km' => 55.0,
                'duration_type' => DurationType::Days,
                'duration_min' => 3,
                'duration_max' => 4,
                'elevation_gain_m' => 2200,
                'max_altitude_m' => 4001,
                'latitude' => -0.4000,
                'longitude' => 36.6500,
                'location_name' => 'Nyeri',
                'region_slug' => 'central',
                'requires_guide' => true,
                'is_year_round' => false,
                'season_notes' => 'Heaviest rainfall April–May and November. Drier windows: January–March and June–October, though mist is common year-round.',
                'accommodation_types' => ['camping', 'huts', 'bandas'],
                'status' => TrailStatus::Published,
                'best_months' => [1, 2, 6, 7, 8, 9, 12],
                'amenity_slugs' => ['camping', 'hut-banda-accommodation', 'guide-available', 'waterfall', 'wildlife-watching', 'bird-watching', 'water-source'],
            ],

            // ── EASTERN ───────────────────────────────────────────────────────
            [
                'name' => 'Ol Donyo Sabuk Circuit',
                'slug' => 'ol-donyo-sabuk-circuit',
                'short_description' => 'A forested hill east of Nairobi with a summit trail through thick indigenous forest to sweeping plains views — habitat for buffalo, colobus monkeys, and over 300 bird species.',
                'description' => "Ol Donyo Sabuk (Kamba for 'big sleeping buffalo') rises to 2,145 m from the Athi Plains, 60 km east of Nairobi. The circuit trail ascends through dense indigenous forest and bamboo groves, following the ridge to the summit plateau where graves of William Northrup McMillan and his household mark the historic peak.\n\nBuffalo freely roam the trails — encounters are common and a ranger escort is available (and advisable). The forest hosts black-and-white colobus, olive baboon, and an exceptional diversity of forest birds. The Fourteen Falls on the Athi River, just below the park, makes a worthwhile add-on.",
                'difficulty' => TrailDifficulty::Moderate,
                'distance_km' => 11.0,
                'duration_type' => DurationType::Hours,
                'duration_min' => 3.5,
                'duration_max' => 5.0,
                'elevation_gain_m' => 750,
                'max_altitude_m' => 2145,
                'latitude' => -1.0667,
                'longitude' => 37.2333,
                'location_name' => 'Thika',
                'region_slug' => 'eastern',
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['forest-trail', 'wildlife-watching', 'bird-watching', 'guide-available', 'parking', 'scenic-viewpoint'],
            ],

            // ── WESTERN ───────────────────────────────────────────────────────
            [
                'name' => 'Kakamega Forest Trail',
                'slug' => 'kakamega-forest-trail',
                'short_description' => "Kenya's only tropical rainforest offers extraordinary birding and primate watching along shaded trails through ancient trees draped in moss and ferns.",
                'description' => "Kakamega Forest is Kenya's last remnant of the ancient Guineo-Congolian rainforest that once stretched across equatorial Africa. The trail network winds through towering Elgon teak and Khaya trees, past streams and clearings rich with birdlife found nowhere else in Kenya.\n\nOver 330 bird species have been recorded, including the Great Blue Turaco, African crowned eagle, and numerous West African species at their easternmost limit. Grey-cheeked mangabeys, red-tailed and blue monkeys, and potto (nocturnal primate) inhabit the forest. The Buyangu Hill viewpoint offers panoramas over the forest canopy.",
                'difficulty' => TrailDifficulty::Easy,
                'distance_km' => 9.0,
                'duration_type' => DurationType::Hours,
                'duration_min' => 3.0,
                'duration_max' => 5.0,
                'elevation_gain_m' => 150,
                'max_altitude_m' => 1650,
                'latitude' => 0.2833,
                'longitude' => 34.8500,
                'location_name' => 'Kakamega',
                'region_slug' => 'western',
                'requires_guide' => true,
                'is_year_round' => true,
                'status' => TrailStatus::Published,
                'amenity_slugs' => ['forest-trail', 'bird-watching', 'wildlife-watching', 'guide-available', 'photography-spot'],
            ],

            // ── COAST ─────────────────────────────────────────────────────────
            [
                'name' => 'Shimba Hills Forest Trail',
                'slug' => 'shimba-hills-forest-trail',
                'short_description' => "The coast's premier forest hike passes Kenya's tallest waterfall and is home to the sable antelope — one of Africa's most beautiful animals — in lush coastal rainforest.",
                'description' => "Shimba Hills National Reserve protects a patch of coastal lowland rainforest 30 km south of Mombasa, rising to 450 m above the Indian Ocean. The forest trail descends to the spectacular Sheldrick Falls — a 21 m cascade into a crystal pool — through forest inhabited by elephant, buffalo, leopard, and the sable antelope, found at very few places in Kenya.\n\nThe coastal forest is botanically distinct from highland forests, with a rich endemic flora. Lion, elephant, and buffalo are present, making guided walks essential. Coastal breezes keep temperatures pleasant even at midday.",
                'difficulty' => TrailDifficulty::Moderate,
                'distance_km' => 12.0,
                'duration_type' => DurationType::Hours,
                'duration_min' => 3.0,
                'duration_max' => 5.0,
                'elevation_gain_m' => 280,
                'max_altitude_m' => 450,
                'latitude' => -4.2167,
                'longitude' => 39.4333,
                'location_name' => 'Kwale',
                'region_slug' => 'coast',
                'requires_guide' => true,
                'is_year_round' => false,
                'season_notes' => 'Best October–March (dry season). Heavy rains April–June make trails slippery and some routes impassable.',
                'status' => TrailStatus::Draft,
                'best_months' => [10, 11, 12, 1, 2, 3],
                'amenity_slugs' => ['waterfall', 'wildlife-watching', 'bird-watching', 'guide-available', 'forest-trail', 'photography-spot'],
            ],
        ];

        $regions = Region::all()->keyBy('slug');

        foreach ($trails as $data) {
            $regionSlug = $data['region_slug'];
            $amenitySlugs = $data['amenity_slugs'] ?? [];
            $bestMonths = $data['best_months'] ?? null;

            unset($data['region_slug'], $data['amenity_slugs'], $data['best_months']);

            $data['created_by'] = $contentManager->id;
            $data['region_id'] = $regions->get($regionSlug)?->id;
            $data['published_at'] = $data['status'] === TrailStatus::Published ? now() : null;

            $trail = Trail::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            if ($bestMonths) {
                $trail->setBestMonths($bestMonths);
            }

            if ($amenitySlugs) {
                $ids = collect($amenitySlugs)
                    ->map(fn (string $slug) => $amenityId($slug))
                    ->filter()
                    ->values()
                    ->all();

                $trail->amenities()->sync($ids);
            }
        }
    }
}
