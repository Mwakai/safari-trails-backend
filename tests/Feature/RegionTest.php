<?php

use App\Models\Region;
use App\Models\Role;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('region model', function () {
    it('creates a region with factory', function () {
        $region = Region::factory()->create();

        expect($region)->toBeInstanceOf(Region::class)
            ->and($region->name)->not->toBeEmpty()
            ->and($region->slug)->not->toBeEmpty()
            ->and($region->is_active)->toBeTrue();
    });

    it('creates an inactive region', function () {
        $region = Region::factory()->inactive()->create();

        expect($region->is_active)->toBeFalse();
    });

    it('creates a region with a specific name', function () {
        $region = Region::factory()->withName('Central')->create();

        expect($region->name)->toBe('Central')
            ->and($region->slug)->toBe('central');
    });

    it('has trails relationship', function () {
        $region = Region::factory()->withName('Central')->create();
        Trail::factory()->withRegion($region)->count(3)->create();

        expect($region->trails)->toHaveCount(3);
    });
});

describe('region scopes', function () {
    it('filters active regions', function () {
        Region::factory()->withName('Central')->create();
        Region::factory()->inactive()->withName('Inactive Region')->create();

        $active = Region::active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->name)->toBe('Central');
    });

    it('orders by sort_order then name', function () {
        Region::factory()->create(['name' => 'Zebra', 'slug' => 'zebra', 'sort_order' => 2]);
        Region::factory()->create(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 1]);
        Region::factory()->create(['name' => 'Beta', 'slug' => 'beta', 'sort_order' => 1]);

        $ordered = Region::ordered()->get();

        expect($ordered[0]->name)->toBe('Alpha')
            ->and($ordered[1]->name)->toBe('Beta')
            ->and($ordered[2]->name)->toBe('Zebra');
    });
});

describe('admin regions endpoint', function () {
    beforeEach(function () {
        $this->adminRole = Role::factory()->admin()->create();
        $this->admin = User::factory()->withRole($this->adminRole)->create();
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/admin/trails/regions');

        $response->assertUnauthorized();
    });

    it('returns active regions', function () {
        Region::factory()->withName('Central')->create();
        Region::factory()->withName('Coast')->create();
        Region::factory()->inactive()->withName('Inactive')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails/regions');

        $response->assertOk()
            ->assertJsonCount(2, 'data.regions');
    });

    it('returns regions in correct order', function () {
        Region::factory()->create(['name' => 'Western', 'slug' => 'western', 'sort_order' => 3]);
        Region::factory()->create(['name' => 'Central', 'slug' => 'central', 'sort_order' => 1]);
        Region::factory()->create(['name' => 'Coast', 'slug' => 'coast', 'sort_order' => 2]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails/regions');

        $response->assertOk();
        $regions = $response->json('data.regions');
        expect($regions[0]['name'])->toBe('Central')
            ->and($regions[1]['name'])->toBe('Coast')
            ->and($regions[2]['name'])->toBe('Western');
    });

    it('returns region structure', function () {
        Region::factory()->withName('Central')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails/regions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'regions' => [
                        '*' => ['id', 'name', 'slug', 'description', 'latitude', 'longitude', 'sort_order', 'is_active'],
                    ],
                ],
            ]);
    });
});
