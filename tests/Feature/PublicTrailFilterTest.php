<?php

use App\Enums\TrailDifficulty;
use App\Models\Amenity;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('public trail listing', function () {
    it('does not require authentication', function () {
        Trail::factory()->published()->create();

        $response = $this->getJson('/api/public/trails');

        $response->assertOk();
    });

    it('only returns published trails', function () {
        Trail::factory()->published()->count(2)->create();
        Trail::factory()->create(); // draft
        Trail::factory()->archived()->create();

        $response = $this->getJson('/api/public/trails');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });

    it('returns correct resource structure', function () {
        Trail::factory()->published()->create();

        $response = $this->getJson('/api/public/trails');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'trails' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'short_description',
                            'difficulty',
                            'distance_km',
                            'duration_hours',
                            'elevation_gain_m',
                            'latitude',
                            'longitude',
                            'location_name',
                            'county',
                            'county_name',
                            'published_at',
                        ],
                    ],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    });
});

describe('admin params ignored', function () {
    it('ignores status filter', function () {
        Trail::factory()->published()->count(2)->create();
        Trail::factory()->create(); // draft

        $response = $this->getJson('/api/public/trails?status=draft');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });

    it('ignores trashed filter', function () {
        Trail::factory()->published()->create();
        $trashedTrail = Trail::factory()->published()->create();
        $trashedTrail->delete();

        $response = $this->getJson('/api/public/trails?trashed=with');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('ignores created_by filter', function () {
        Trail::factory()->published()->count(2)->create();

        $response = $this->getJson('/api/public/trails?created_by=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });
});

describe('public filters', function () {
    it('filters by search', function () {
        Trail::factory()->published()->create(['name' => 'Chania Falls Trail']);
        Trail::factory()->published()->create(['name' => 'Ngong Hills']);

        $response = $this->getJson('/api/public/trails?search=Chania');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by difficulty', function () {
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Easy)->create();
        Trail::factory()->published()->withDifficulty(TrailDifficulty::Expert)->create();

        $response = $this->getJson('/api/public/trails?difficulty=easy');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by county', function () {
        Trail::factory()->published()->withCounty('nyeri')->create();
        Trail::factory()->published()->withCounty('nairobi')->create();

        $response = $this->getJson('/api/public/trails?county=nyeri');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by amenities', function () {
        $amenity = Amenity::factory()->withName('Waterfall')->create();

        $trailWith = Trail::factory()->published()->create();
        $trailWith->amenities()->attach($amenity->id);

        Trail::factory()->published()->create();

        $response = $this->getJson("/api/public/trails?amenities={$amenity->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by distance range', function () {
        Trail::factory()->published()->create(['distance_km' => 5.00]);
        Trail::factory()->published()->create(['distance_km' => 20.00]);

        $response = $this->getJson('/api/public/trails?min_distance=10&max_distance=30');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by duration range', function () {
        Trail::factory()->published()->create(['duration_hours' => 2.0]);
        Trail::factory()->published()->create(['duration_hours' => 8.0]);

        $response = $this->getJson('/api/public/trails?min_duration=5');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('sorts trails', function () {
        Trail::factory()->published()->create(['name' => 'Zebra Trail']);
        Trail::factory()->published()->create(['name' => 'Alpha Trail']);

        $response = $this->getJson('/api/public/trails?sort=name&order=asc');

        $response->assertOk();
        $trails = $response->json('data.trails');
        expect($trails[0]['name'])->toBe('Alpha Trail');
    });

    it('filters by date range', function () {
        Trail::factory()->published()->create(['created_at' => now()->subDays(10)]);
        Trail::factory()->published()->create(['created_at' => now()]);

        $date = now()->subDays(5)->format('Y-m-d');

        $response = $this->getJson("/api/public/trails?created_after={$date}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('accepts per_page parameter', function () {
        Trail::factory()->published()->count(10)->create();

        $response = $this->getJson('/api/public/trails?per_page=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data.trails')
            ->assertJsonPath('data.meta.per_page', 3);
    });
});

describe('geo filters', function () {
    it('filters by bounding box', function () {
        Trail::factory()->published()->create(['latitude' => -1.28, 'longitude' => 36.82]); // Nairobi
        Trail::factory()->published()->create(['latitude' => 0.05, 'longitude' => 37.65]); // Mt Kenya area

        $response = $this->getJson('/api/public/trails?bounds=-1.5,36.5,-1.0,37.0');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by proximity', function () {
        Trail::factory()->published()->create(['latitude' => -1.28, 'longitude' => 36.82]); // Nairobi
        Trail::factory()->published()->create(['latitude' => 0.05, 'longitude' => 37.65]); // ~180km away

        $response = $this->getJson('/api/public/trails?near_lat=-1.28&near_lng=36.82&radius=50');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    })->skip(fn () => DB::connection()->getDriverName() === 'sqlite', 'Haversine requires MySQL math functions');

    it('uses default radius of 25km', function () {
        Trail::factory()->published()->create(['latitude' => -1.28, 'longitude' => 36.82]);
        Trail::factory()->published()->create(['latitude' => -1.58, 'longitude' => 36.82]); // ~33km away

        $response = $this->getJson('/api/public/trails?near_lat=-1.28&near_lng=36.82');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    })->skip(fn () => DB::connection()->getDriverName() === 'sqlite', 'Haversine requires MySQL math functions');

    it('caps radius at 200km', function () {
        $response = $this->getJson('/api/public/trails?near_lat=-1.28&near_lng=36.82&radius=500');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['radius']);
    });

    it('requires both near_lat and near_lng', function () {
        $response = $this->getJson('/api/public/trails?near_lat=-1.28');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['near_lng']);
    });

    it('validates bounds format', function () {
        $response = $this->getJson('/api/public/trails?bounds=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['bounds']);
    });
});

describe('public trail show', function () {
    it('returns a published trail by slug', function () {
        $trail = Trail::factory()->published()->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}");

        $response->assertOk()
            ->assertJsonPath('data.trail.name', $trail->name)
            ->assertJsonPath('data.trail.slug', $trail->slug);
    });

    it('returns 404 for draft trail', function () {
        $trail = Trail::factory()->create(); // draft

        $response = $this->getJson("/api/public/trails/{$trail->slug}");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent slug', function () {
        $response = $this->getJson('/api/public/trails/non-existent-trail');

        $response->assertNotFound();
    });
});
