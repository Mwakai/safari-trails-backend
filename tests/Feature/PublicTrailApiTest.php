<?php

use App\Enums\TrailDifficulty;
use App\Models\Amenity;
use App\Models\Media;
use App\Models\Region;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('map markers', function () {
    it('does not require authentication', function () {
        Trail::factory()->published()->create();

        $response = $this->getJson('/api/public/trails/map');

        $response->assertSuccessful();
    });

    it('returns all published trails without pagination', function () {
        Trail::factory()->published()->count(5)->create();
        Trail::factory()->create(); // draft
        Trail::factory()->archived()->create();

        $response = $this->getJson('/api/public/trails/map');

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data.trails')
            ->assertJsonPath('data.meta.total', 5)
            ->assertJsonMissing(['current_page', 'last_page', 'per_page']);
    });

    it('returns correct lightweight structure', function () {
        Trail::factory()->published()->create();

        $response = $this->getJson('/api/public/trails/map');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'trails' => [
                        '*' => [
                            'id',
                            'slug',
                            'name',
                            'latitude',
                            'longitude',
                            'difficulty',
                            'difficulty_label',
                            'distance_km',
                            'duration_type',
                            'duration_min',
                            'duration_max',
                            'is_multi_day',
                            'duration_display',
                            'elevation_gain_m',
                            'thumbnail_url',
                            'region_slug',
                            'region_name',
                        ],
                    ],
                    'meta' => ['total', 'bounds_applied'],
                ],
            ]);
    });

    it('excludes heavy fields from response', function () {
        Trail::factory()->published()->create();

        $response = $this->getJson('/api/public/trails/map');

        $trail = $response->json('data.trails.0');
        expect($trail)->not->toHaveKeys(['description', 'short_description', 'location_name', 'published_at', 'amenities', 'featured_image']);
    });

    it('includes thumbnail URL when featured image has variant', function () {
        $media = Media::factory()->create([
            'variants' => ['thumbnail' => 'uploads/thumb.jpg'],
        ]);
        Trail::factory()->published()->create(['featured_image_id' => $media->id]);

        $response = $this->getJson('/api/public/trails/map');

        $trail = $response->json('data.trails.0');
        expect($trail['thumbnail_url'])->not->toBeNull();
    });

    it('returns null thumbnail when no featured image', function () {
        Trail::factory()->published()->create(['featured_image_id' => null]);

        $response = $this->getJson('/api/public/trails/map');

        $trail = $response->json('data.trails.0');
        expect($trail['thumbnail_url'])->toBeNull();
    });

    it('limits results to 500', function () {
        Trail::factory()->published()->count(502)->create();

        $response = $this->getJson('/api/public/trails/map');

        $response->assertSuccessful();
        expect(count($response->json('data.trails')))->toBeLessThanOrEqual(500);
    });

    it('filters by difficulty', function () {
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->create();
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Expert)->create();

        $response = $this->getJson('/api/public/trails/map?difficulty=easy');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by region', function () {
        $central = Region::factory()->withName('Central')->create();
        $nairobi = Region::factory()->withName('Nairobi')->create();
        Trail::factory()->published()->withRegion($central)->create();
        Trail::factory()->published()->withRegion($nairobi)->create();

        $response = $this->getJson('/api/public/trails/map?region=central');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by bounding box', function () {
        Trail::factory()->published()->create(['latitude' => -1.28, 'longitude' => 36.82]); // Nairobi
        Trail::factory()->published()->create(['latitude' => 0.05, 'longitude' => 37.65]); // Mt Kenya

        $response = $this->getJson('/api/public/trails/map?bounds=-1.5,36.5,-1.0,37.0');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data.trails')
            ->assertJsonPath('data.meta.bounds_applied', true);
    });

    it('reports bounds_applied as false when no bounds', function () {
        Trail::factory()->published()->create();

        $response = $this->getJson('/api/public/trails/map');

        $response->assertJsonPath('data.meta.bounds_applied', false);
    });

    it('validates bounds format', function () {
        $response = $this->getJson('/api/public/trails/map?bounds=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['bounds']);
    });

    it('filters by proximity', function () {
        Trail::factory()->published()->create(['latitude' => -1.28, 'longitude' => 36.82]); // Nairobi
        Trail::factory()->published()->create(['latitude' => 0.05, 'longitude' => 37.65]); // ~180km away

        $response = $this->getJson('/api/public/trails/map?near_lat=-1.28&near_lng=36.82&radius=50');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data.trails');
    })->skip(fn () => DB::connection()->getDriverName() === 'sqlite', 'Haversine requires MySQL math functions');
});

describe('related trails', function () {
    it('does not require authentication', function () {
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $response->assertSuccessful();
    });

    it('returns related trails for a published trail', function () {
        $region = Region::factory()->withName('Central')->create();
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($region)->create();
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($region)->count(3)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data.trails');
    });

    it('excludes the current trail from results', function () {
        $region = Region::factory()->withName('Central')->create();
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->withRegion($region)->create();
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->withRegion($region)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $ids = collect($response->json('data.trails'))->pluck('id');
        expect($ids)->not->toContain($trail->id);
    });

    it('prioritizes same region trails', function () {
        $central = Region::factory()->withName('Central')->create();
        $nairobi = Region::factory()->withName('Nairobi')->create();
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($central)->create();

        // Same region, different difficulty
        $sameRegion = Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->withRegion($central)->create();
        // Different region, adjacent difficulty
        $diffRegion = Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->withRegion($nairobi)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $trails = $response->json('data.trails');
        expect($trails[0]['id'])->toBe($sameRegion->id);
    });

    it('limits results to 6', function () {
        $region = Region::factory()->withName('Central')->create();
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($region)->create();
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($region)->count(10)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $response->assertSuccessful();
        expect(count($response->json('data.trails')))->toBeLessThanOrEqual(6);
    });

    it('includes adjacent difficulty levels', function () {
        $central = Region::factory()->withName('Central')->create();
        $coast = Region::factory()->withName('Coast')->create();
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($central)->create();

        // Adjacent: Easy and Difficult
        $easy = Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->withRegion($coast)->create();
        $difficult = Trail::factory()->published()->withDifficulty(TrailDifficulty::Difficult)->withRegion($coast)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $ids = collect($response->json('data.trails'))->pluck('id');
        expect($ids)->toContain($easy->id)
            ->toContain($difficult->id);
    });

    it('excludes non-adjacent difficulty levels from different region', function () {
        $central = Region::factory()->withName('Central')->create();
        $coast = Region::factory()->withName('Coast')->create();
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->withRegion($central)->create();

        // Expert is 2 steps away from Easy, and different region
        $expert = Trail::factory()->published()->withDifficulty(TrailDifficulty::Expert)->withRegion($coast)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $ids = collect($response->json('data.trails'))->pluck('id');
        expect($ids)->not->toContain($expert->id);
    });

    it('returns 404 for draft trail', function () {
        $trail = Trail::factory()->create(); // draft

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent slug', function () {
        $response = $this->getJson('/api/public/trails/non-existent-trail/related');

        $response->assertNotFound();
    });

    it('uses MapMarkerResource format', function () {
        $region = Region::factory()->withName('Central')->create();
        $trail = Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($region)->create();
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->withRegion($region)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}/related");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'trails' => [
                        '*' => [
                            'id',
                            'slug',
                            'name',
                            'latitude',
                            'longitude',
                            'difficulty',
                            'difficulty_label',
                            'distance_km',
                            'duration_type',
                            'duration_min',
                            'duration_max',
                            'is_multi_day',
                            'duration_display',
                            'elevation_gain_m',
                            'thumbnail_url',
                            'region_slug',
                            'region_name',
                        ],
                    ],
                ],
            ]);
    });
});

describe('public regions', function () {
    it('does not require authentication', function () {
        $response = $this->getJson('/api/public/trails/regions');

        $response->assertSuccessful();
    });

    it('returns correct structure', function () {
        $region = Region::factory()->withName('Central')->create();
        Trail::factory()->published()->withRegion($region)->create();

        $response = $this->getJson('/api/public/trails/regions');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'regions' => [
                        '*' => ['id', 'slug', 'name', 'trails_count'],
                    ],
                ],
            ]);
    });

    it('only includes regions with published trails', function () {
        $central = Region::factory()->withName('Central')->create();
        $nairobi = Region::factory()->withName('Nairobi')->create();
        $coast = Region::factory()->withName('Coast')->create();
        Trail::factory()->published()->withRegion($central)->create();
        Trail::factory()->published()->withRegion($nairobi)->create();
        Trail::factory()->withRegion($coast)->create(); // draft

        $response = $this->getJson('/api/public/trails/regions');

        $slugs = collect($response->json('data.regions'))->pluck('slug');
        expect($slugs)->toContain('central')
            ->toContain('nairobi')
            ->not->toContain('coast');
    });

    it('returns correct published trail count per region', function () {
        $central = Region::factory()->withName('Central')->create();
        Trail::factory()->published()->withRegion($central)->count(4)->create();
        Trail::factory()->withRegion($central)->create(); // draft should not count

        $response = $this->getJson('/api/public/trails/regions');

        $centralRegion = collect($response->json('data.regions'))->firstWhere('slug', 'central');
        expect($centralRegion['trails_count'])->toBe(4);
    });

    it('excludes inactive regions', function () {
        $active = Region::factory()->withName('Central')->create();
        $inactive = Region::factory()->inactive()->withName('Hidden Region')->create();
        Trail::factory()->published()->withRegion($active)->create();
        Trail::factory()->published()->withRegion($inactive)->create();

        $response = $this->getJson('/api/public/trails/regions');

        $slugs = collect($response->json('data.regions'))->pluck('slug');
        expect($slugs)->toContain('central')
            ->not->toContain('hidden-region');
    });

    it('returns empty list when no published trails exist', function () {
        Region::factory()->withName('Central')->create();

        $response = $this->getJson('/api/public/trails/regions');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data.regions');
    });
});

describe('filter options', function () {
    it('does not require authentication', function () {
        $response = $this->getJson('/api/public/trails/filters');

        $response->assertSuccessful();
    });

    it('returns correct structure', function () {
        Trail::factory()->published()->create();

        $response = $this->getJson('/api/public/trails/filters');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'regions' => [
                        '*' => ['slug', 'name', 'trails_count'],
                    ],
                    'difficulties' => [
                        '*' => ['value', 'label', 'trails_count'],
                    ],
                    'amenities' => [
                        '*' => ['id', 'name', 'slug', 'trails_count'],
                    ],
                    'duration_types' => [
                        '*' => ['value', 'label'],
                    ],
                ],
            ]);
    });

    it('only includes regions with published trails', function () {
        $central = Region::factory()->withName('Central')->create();
        $nairobi = Region::factory()->withName('Nairobi')->create();
        $coast = Region::factory()->withName('Coast')->create();
        Trail::factory()->published()->withRegion($central)->create();
        Trail::factory()->published()->withRegion($nairobi)->create();
        Trail::factory()->withRegion($coast)->create(); // draft

        $response = $this->getJson('/api/public/trails/filters');

        $regionSlugs = collect($response->json('data.regions'))->pluck('slug');
        expect($regionSlugs)->toContain('central')
            ->toContain('nairobi')
            ->not->toContain('coast');
    });

    it('returns correct count per region', function () {
        $central = Region::factory()->withName('Central')->create();
        $nairobi = Region::factory()->withName('Nairobi')->create();
        Trail::factory()->published()->withRegion($central)->count(3)->create();
        Trail::factory()->published()->withRegion($nairobi)->create();

        $response = $this->getJson('/api/public/trails/filters');

        $centralRegion = collect($response->json('data.regions'))->firstWhere('slug', 'central');
        expect($centralRegion['trails_count'])->toBe(3);
    });

    it('returns region names', function () {
        $central = Region::factory()->withName('Central')->create();
        Trail::factory()->published()->withRegion($central)->create();

        $response = $this->getJson('/api/public/trails/filters');

        $centralRegion = collect($response->json('data.regions'))->firstWhere('slug', 'central');
        expect($centralRegion['name'])->toBe('Central');
    });

    it('returns difficulty labels', function () {
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Moderate)->create();

        $response = $this->getJson('/api/public/trails/filters');

        $moderate = collect($response->json('data.difficulties'))->firstWhere('value', 'moderate');
        expect($moderate['label'])->toBe('Moderate');
    });

    it('only includes amenities attached to published trails', function () {
        $amenity = Amenity::factory()->withName('Waterfall')->create();
        $unusedAmenity = Amenity::factory()->withName('Camping')->create();

        $trail = Trail::factory()->published()->create();
        $trail->amenities()->attach($amenity->id);

        $response = $this->getJson('/api/public/trails/filters');

        $amenityNames = collect($response->json('data.amenities'))->pluck('name');
        expect($amenityNames)->toContain('Waterfall')
            ->not->toContain('Camping');
    });

    it('excludes inactive amenities', function () {
        $activeAmenity = Amenity::factory()->withName('Waterfall')->create();
        $inactiveAmenity = Amenity::factory()->inactive()->withName('Closed Area')->create();

        $trail = Trail::factory()->published()->create();
        $trail->amenities()->attach([$activeAmenity->id, $inactiveAmenity->id]);

        $response = $this->getJson('/api/public/trails/filters');

        $amenityNames = collect($response->json('data.amenities'))->pluck('name');
        expect($amenityNames)->toContain('Waterfall')
            ->not->toContain('Closed Area');
    });

    it('returns amenities with correct counts', function () {
        $amenity = Amenity::factory()->withName('Scenic View')->create();

        $trails = Trail::factory()->published()->count(3)->create();
        foreach ($trails as $trail) {
            $trail->amenities()->attach($amenity->id);
        }

        // Draft trail with same amenity should not count
        $draftTrail = Trail::factory()->create();
        $draftTrail->amenities()->attach($amenity->id);

        $response = $this->getJson('/api/public/trails/filters');

        $scenic = collect($response->json('data.amenities'))->firstWhere('name', 'Scenic View');
        expect($scenic['trails_count'])->toBe(3);
    });
});
