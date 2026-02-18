<?php

use App\Enums\DurationType;
use App\Models\Amenity;
use App\Models\Region;
use App\Models\Role;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
    $this->region = Region::factory()->withName('Central')->create();
});

function durationPayload(array $overrides = []): array
{
    static $region = null;

    if ($region === null) {
        $region = Region::where('slug', 'central')->first() ?? Region::factory()->withName('Central Test')->create();
    }

    return array_merge([
        'name' => 'Duration Test Trail',
        'description' => 'A test trail for duration features',
        'difficulty' => 'moderate',
        'distance_km' => 10.0,
        'duration_type' => 'hours',
        'duration_min' => 3.0,
        'latitude' => -1.28,
        'longitude' => 36.82,
        'location_name' => 'Test Location',
        'region_id' => $region->id,
    ], $overrides);
}

describe('duration display accessor', function () {
    it('displays single hours value', function () {
        $trail = Trail::factory()->withDuration(DurationType::Hours, 5.0)->create();

        expect($trail->duration_display)->toBe('5 hours');
    });

    it('displays hours range', function () {
        $trail = Trail::factory()->withDuration(DurationType::Hours, 2.0, 3.0)->create();

        expect($trail->duration_display)->toBe('2-3 hours');
    });

    it('displays single days value', function () {
        $trail = Trail::factory()->withDuration(DurationType::Days, 5.0)->create();

        expect($trail->duration_display)->toBe('5 days');
    });

    it('displays days range', function () {
        $trail = Trail::factory()->withDuration(DurationType::Days, 4.0, 5.0)->create();

        expect($trail->duration_display)->toBe('4-5 days');
    });

    it('displays fractional values correctly', function () {
        $trail = Trail::factory()->withDuration(DurationType::Hours, 2.5, 3.5)->create();

        expect($trail->duration_display)->toBe('2.5-3.5 hours');
    });

    it('treats same min and max as single value', function () {
        $trail = Trail::factory()->withDuration(DurationType::Hours, 3.0, 3.0)->create();

        expect($trail->duration_display)->toBe('3 hours');
    });
});

describe('is_multi_day accessor', function () {
    it('returns true for days type', function () {
        $trail = Trail::factory()->multiDay()->create();

        expect($trail->is_multi_day)->toBeTrue();
    });

    it('returns false for hours type', function () {
        $trail = Trail::factory()->create();

        expect($trail->is_multi_day)->toBeFalse();
    });
});

describe('best months', function () {
    it('sets and gets best months', function () {
        $trail = Trail::factory()->create(['is_year_round' => false]);
        $trail->setBestMonths([1, 2, 3, 7, 8]);

        expect($trail->getBestMonthsArray())->toBe([1, 2, 3, 7, 8]);
    });

    it('displays formatted month ranges', function () {
        $trail = Trail::factory()->create(['is_year_round' => false]);
        $trail->setBestMonths([1, 2, 3, 7, 8, 9, 10]);
        $trail->load('bestMonths');

        expect($trail->best_months_display)->toBe('Jan-Mar, Jul-Oct');
    });

    it('displays Year-round when is_year_round is true', function () {
        $trail = Trail::factory()->create(['is_year_round' => true]);

        expect($trail->best_months_display)->toBe('Year-round');
    });

    it('displays single month', function () {
        $trail = Trail::factory()->create(['is_year_round' => false]);
        $trail->setBestMonths([6]);
        $trail->load('bestMonths');

        expect($trail->best_months_display)->toBe('Jun');
    });

    it('deduplicates months', function () {
        $trail = Trail::factory()->create(['is_year_round' => false]);
        $trail->setBestMonths([1, 1, 2, 2, 3]);

        expect($trail->getBestMonthsArray())->toBe([1, 2, 3]);
    });

    it('filters invalid months', function () {
        $trail = Trail::factory()->create(['is_year_round' => false]);
        $trail->setBestMonths([0, 1, 13, 2, -1]);

        expect($trail->getBestMonthsArray())->toBe([1, 2]);
    });

    it('isGoodMonth returns true for year-round trails', function () {
        $trail = Trail::factory()->create(['is_year_round' => true]);

        expect($trail->isGoodMonth(6))->toBeTrue();
    });

    it('isGoodMonth checks best months for seasonal trails', function () {
        $trail = Trail::factory()->create(['is_year_round' => false]);
        $trail->setBestMonths([1, 2, 3]);

        expect($trail->isGoodMonth(1))->toBeTrue();
        expect($trail->isGoodMonth(6))->toBeFalse();
    });
});

describe('CRUD integration with new fields', function () {
    it('creates trail with all duration and season fields', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'duration_type' => 'days',
                'duration_min' => 3.0,
                'duration_max' => 5.0,
                'is_year_round' => false,
                'season_notes' => 'Best during dry season',
                'best_months' => [1, 2, 7, 8],
                'requires_guide' => true,
                'requires_permit' => true,
                'permit_info' => 'KWS permit required',
                'accommodation_types' => ['camping', 'huts'],
            ]));

        $response->assertCreated();
        $trail = Trail::find($response->json('data.trail.id'));

        expect($trail->duration_type)->toBe(DurationType::Days);
        expect((float) $trail->duration_min)->toBe(3.0);
        expect((float) $trail->duration_max)->toBe(5.0);
        expect($trail->is_year_round)->toBeFalse();
        expect($trail->season_notes)->toBe('Best during dry season');
        expect($trail->requires_guide)->toBeTrue();
        expect($trail->requires_permit)->toBeTrue();
        expect($trail->permit_info)->toBe('KWS permit required');
        expect($trail->accommodation_types)->toBe(['camping', 'huts']);
        expect($trail->getBestMonthsArray())->toBe([1, 2, 7, 8]);
    });

    it('returns new fields in detail response', function () {
        $trail = Trail::factory()
            ->multiDay()
            ->withGuideRequired()
            ->withPermitRequired('KWS permit')
            ->withAccommodation(['camping'])
            ->create(['is_year_round' => false]);
        $trail->setBestMonths([1, 2, 3]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails/{$trail->id}");

        $response->assertOk()
            ->assertJsonPath('data.trail.duration_type', 'days')
            ->assertJsonPath('data.trail.is_multi_day', true)
            ->assertJsonPath('data.trail.requires_guide', true)
            ->assertJsonPath('data.trail.requires_permit', true)
            ->assertJsonPath('data.trail.permit_info', 'KWS permit')
            ->assertJsonPath('data.trail.accommodation_types', ['camping'])
            ->assertJsonPath('data.trail.is_year_round', false)
            ->assertJsonPath('data.trail.best_months', [1, 2, 3]);
    });

    it('includes season accessors in detail but not in list', function () {
        Trail::factory()->create();

        // List resource should NOT have season accessors
        $listResponse = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails');

        $listTrail = $listResponse->json('data.trails.0');
        expect($listTrail)->not->toHaveKey('best_months');
        expect($listTrail)->not->toHaveKey('current_month_rating');
        expect($listTrail)->not->toHaveKey('season_recommendation');

        // But should have duration display
        expect($listTrail)->toHaveKey('duration_display');
        expect($listTrail)->toHaveKey('is_multi_day');
    });
});

describe('validation', function () {
    it('requires duration_type', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'duration_type' => null,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_type']);
    });

    it('validates duration_type enum values', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'duration_type' => 'weeks',
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_type']);
    });

    it('validates duration_max >= duration_min', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'duration_min' => 5.0,
                'duration_max' => 2.0,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['duration_max']);
    });

    it('requires best_months when not year_round', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'is_year_round' => false,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['best_months']);
    });

    it('does not require best_months when year_round', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'is_year_round' => true,
            ]));

        $response->assertCreated();
    });

    it('requires permit_info when requires_permit is true', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'requires_permit' => true,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['permit_info']);
    });

    it('validates accommodation_types values', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'accommodation_types' => ['camping', 'treehouses'],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['accommodation_types.1']);
    });

    it('validates best_months range 1-12', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'is_year_round' => false,
                'best_months' => [0, 13],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['best_months.0', 'best_months.1']);
    });
});

describe('camping amenity auto-sync', function () {
    it('attaches camping amenity when accommodation_types includes camping', function () {
        $campingAmenity = Amenity::factory()->create(['name' => 'Camping', 'slug' => 'camping']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'accommodation_types' => ['camping', 'huts'],
            ]));

        $response->assertCreated();
        $trailId = $response->json('data.trail.id');

        $this->assertDatabaseHas('trail_amenity', [
            'trail_id' => $trailId,
            'amenity_id' => $campingAmenity->id,
        ]);
    });

    it('detaches camping amenity when camping removed from accommodation_types', function () {
        $campingAmenity = Amenity::factory()->create(['name' => 'Camping', 'slug' => 'camping']);

        // Create with camping
        $trail = Trail::factory()->withAccommodation(['camping', 'huts'])->create();
        $trail->amenities()->attach($campingAmenity->id);

        // Update without camping
        $this->actingAs($this->admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'accommodation_types' => ['huts'],
            ]);

        $this->assertDatabaseMissing('trail_amenity', [
            'trail_id' => $trail->id,
            'amenity_id' => $campingAmenity->id,
        ]);
    });

    it('does not fail when no Camping amenity exists', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'accommodation_types' => ['camping'],
            ]));

        $response->assertCreated();
    });

    it('preserves camping amenity when explicitly included in amenity_ids', function () {
        $campingAmenity = Amenity::factory()->create(['name' => 'Camping', 'slug' => 'camping']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/trails', durationPayload([
                'accommodation_types' => ['huts'],
                'amenity_ids' => [$campingAmenity->id],
            ]));

        $response->assertCreated();
        $trailId = $response->json('data.trail.id');

        // Camping amenity should remain because it was explicitly passed
        $this->assertDatabaseHas('trail_amenity', [
            'trail_id' => $trailId,
            'amenity_id' => $campingAmenity->id,
        ]);
    });
});
