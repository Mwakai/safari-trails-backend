<?php

use App\Models\Region;
use App\Models\Role;
use App\Models\Trail;
use App\Models\TrailItineraryDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
    $this->region = Region::factory()->withName('Central')->create();
});

function itineraryPayload(array $overrides = []): array
{
    static $region = null;

    if ($region === null) {
        $region = Region::where('slug', 'central')->first() ?? Region::factory()->withName('Central Itin')->create();
    }

    return array_merge([
        'name' => 'Itinerary Test Trail',
        'description' => 'A test trail for itinerary features',
        'difficulty' => 'moderate',
        'distance_km' => 30.0,
        'duration_type' => 'days',
        'duration_min' => 3.0,
        'latitude' => -1.28,
        'longitude' => 36.82,
        'location_name' => 'Test Location',
        'region_id' => $region->id,
    ], $overrides);
}

describe('create trail with itinerary', function () {
    it('creates trail with itinerary days', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Day 1: Trailhead to Camp', 'description' => 'Begin the hike', 'distance_km' => 12.5, 'elevation_gain_m' => 600, 'start_point' => 'Trailhead', 'end_point' => 'Base Camp', 'accommodation' => 'Camping'],
                    ['day_number' => 2, 'title' => 'Day 2: Camp to Summit', 'description' => 'Summit day', 'distance_km' => 8.0, 'elevation_gain_m' => 1200, 'start_point' => 'Base Camp', 'end_point' => 'Summit Camp', 'accommodation' => 'Mountain hut'],
                    ['day_number' => 3, 'title' => 'Day 3: Descent', 'distance_km' => 15.0, 'start_point' => 'Summit Camp', 'end_point' => 'Trailhead'],
                ],
            ]));

        $response->assertCreated();
        $trailId = $response->json('data.trail.id');

        $this->assertDatabaseCount('trail_itinerary_days', 3);
        $this->assertDatabaseHas('trail_itinerary_days', [
            'trail_id' => $trailId,
            'day_number' => 1,
            'title' => 'Day 1: Trailhead to Camp',
            'distance_km' => 12.50,
            'accommodation' => 'Camping',
        ]);
        $this->assertDatabaseHas('trail_itinerary_days', [
            'trail_id' => $trailId,
            'day_number' => 3,
            'title' => 'Day 3: Descent',
        ]);
    });

    it('creates trail without itinerary days', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload());

        $response->assertCreated();
        $this->assertDatabaseCount('trail_itinerary_days', 0);
    });

    it('creates trail with empty itinerary array', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [],
            ]));

        $response->assertCreated();
        $this->assertDatabaseCount('trail_itinerary_days', 0);
    });

    it('returns itinerary days in create response', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Day 1: Start'],
                    ['day_number' => 2, 'title' => 'Day 2: Finish'],
                ],
            ]));

        $response->assertCreated()
            ->assertJsonCount(2, 'data.trail.itinerary_days')
            ->assertJsonPath('data.trail.itinerary_days.0.day_number', 1)
            ->assertJsonPath('data.trail.itinerary_days.0.title', 'Day 1: Start')
            ->assertJsonPath('data.trail.itinerary_days.1.day_number', 2);
    });
});

describe('update trail itinerary', function () {
    it('replaces itinerary days on update', function () {
        $trail = Trail::factory()->withItinerary(3)->create();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'New Day 1'],
                    ['day_number' => 2, 'title' => 'New Day 2'],
                ],
            ]);

        $response->assertOk();
        expect($trail->fresh()->itineraryDays)->toHaveCount(2);
        expect($trail->fresh()->itineraryDays->first()->title)->toBe('New Day 1');
    });

    it('removes itinerary days when empty array sent', function () {
        $trail = Trail::factory()->withItinerary(3)->create();
        expect($trail->itineraryDays)->toHaveCount(3);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'itinerary_days' => [],
            ]);

        $response->assertOk();
        expect($trail->fresh()->itineraryDays)->toHaveCount(0);
    });

    it('does not touch itinerary when key is absent from update payload', function () {
        $trail = Trail::factory()->withItinerary(2)->create();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'name' => 'Updated Trail Name',
            ]);

        $response->assertOk();
        expect($trail->fresh()->itineraryDays)->toHaveCount(2);
    });
});

describe('detail views include itinerary', function () {
    it('includes itinerary days in admin show response', function () {
        $trail = Trail::factory()->withItinerary(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails/{$trail->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.trail.itinerary_days')
            ->assertJsonStructure([
                'data' => [
                    'trail' => [
                        'itinerary_days' => [
                            '*' => ['id', 'day_number', 'title', 'description', 'distance_km', 'elevation_gain_m', 'start_point', 'end_point', 'accommodation', 'sort_order'],
                        ],
                    ],
                ],
            ]);
    });

    it('includes itinerary days in public show response', function () {
        $trail = Trail::factory()->published()->withItinerary(3)->create();

        $response = $this->getJson("/api/public/trails/{$trail->slug}");

        $response->assertOk()
            ->assertJsonCount(3, 'data.trail.itinerary_days');
    });
});

describe('list views exclude itinerary', function () {
    it('excludes itinerary days from admin list response', function () {
        Trail::factory()->withItinerary(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails');

        $response->assertOk();
        $trail = $response->json('data.trails.0');
        expect($trail)->not->toHaveKey('itinerary_days');
    });

    it('excludes itinerary days from public list response', function () {
        Trail::factory()->published()->withItinerary(2)->create();

        $response = $this->getJson('/api/public/trails');

        $response->assertOk();
        $trail = $response->json('data.trails.0');
        expect($trail)->not->toHaveKey('itinerary_days');
    });
});

describe('ordering', function () {
    it('returns itinerary days ordered by day_number', function () {
        $trail = Trail::factory()->create();

        TrailItineraryDay::factory()->forDay(3)->create(['trail_id' => $trail->id]);
        TrailItineraryDay::factory()->forDay(1)->create(['trail_id' => $trail->id]);
        TrailItineraryDay::factory()->forDay(2)->create(['trail_id' => $trail->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails/{$trail->id}");

        $days = $response->json('data.trail.itinerary_days');
        expect($days[0]['day_number'])->toBe(1);
        expect($days[1]['day_number'])->toBe(2);
        expect($days[2]['day_number'])->toBe(3);
    });
});

describe('validation', function () {
    it('requires day_number for each itinerary day', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['title' => 'Day without number'],
                ],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_days.0.day_number']);
    });

    it('requires title for each itinerary day', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 1],
                ],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_days.0.title']);
    });

    it('rejects day_number less than 1', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 0, 'title' => 'Invalid day'],
                ],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_days.0.day_number']);
    });

    it('rejects non-integer day_number', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 'abc', 'title' => 'Invalid day'],
                ],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_days.0.day_number']);
    });

    it('rejects duplicate day_numbers within payload', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Day 1: First'],
                    ['day_number' => 1, 'title' => 'Day 1: Duplicate'],
                ],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_days']);
    });

    it('allows gaps in day_numbers', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Day 1'],
                    ['day_number' => 3, 'title' => 'Day 3'],
                    ['day_number' => 5, 'title' => 'Day 5'],
                ],
            ]));

        $response->assertCreated();
        $this->assertDatabaseCount('trail_itinerary_days', 3);
    });

    it('validates distance_km is numeric', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Day 1', 'distance_km' => 'not-a-number'],
                ],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_days.0.distance_km']);
    });

    it('validates elevation_gain_m is integer', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', itineraryPayload([
                'itinerary_days' => [
                    ['day_number' => 1, 'title' => 'Day 1', 'elevation_gain_m' => 'high'],
                ],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['itinerary_days.0.elevation_gain_m']);
    });
});

describe('factory states', function () {
    it('creates trail with itinerary using factory', function () {
        $trail = Trail::factory()->withItinerary(4)->create();

        expect($trail->itineraryDays)->toHaveCount(4);
        expect($trail->is_multi_day)->toBeTrue();
        expect($trail->itineraryDays->first()->day_number)->toBe(1);
        expect($trail->itineraryDays->last()->day_number)->toBe(4);
    });

    it('creates itinerary day with forDay state', function () {
        $trail = Trail::factory()->create();
        $day = TrailItineraryDay::factory()->forDay(5)->create(['trail_id' => $trail->id]);

        expect($day->day_number)->toBe(5);
        expect($day->sort_order)->toBe(4);
        expect($day->title)->toStartWith('Day 5:');
    });
});

describe('cascade delete', function () {
    it('deletes itinerary days when trail is force deleted', function () {
        $trail = Trail::factory()->withItinerary(3)->create();
        $this->assertDatabaseCount('trail_itinerary_days', 3);

        $trail->forceDelete();

        $this->assertDatabaseCount('trail_itinerary_days', 0);
    });
});
