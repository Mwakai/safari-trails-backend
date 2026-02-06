<?php

use App\Models\Amenity;
use App\Models\Role;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTrail(array $attributes = []): Trail
{
    static $counter = 0;
    $counter++;

    return Trail::factory()->create(array_merge([
        'name' => "Filter Trail {$counter}",
        'slug' => 'filter-trail-'.$counter.'-'.Str::random(4),
    ], $attributes));
}

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
});

describe('pagination', function () {
    it('uses default per_page of 15', function () {
        foreach (range(1, 20) as $i) {
            createTrail();
        }

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 15);
    });

    it('accepts custom per_page', function () {
        foreach (range(1, 10) as $i) {
            createTrail();
        }

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?per_page=5');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonCount(5, 'data.trails');
    });

    it('rejects per_page over 100', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    it('accepts page parameter', function () {
        foreach (range(1, 20) as $i) {
            createTrail();
        }

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?per_page=5&page=2');

        $response->assertOk()
            ->assertJsonPath('data.meta.current_page', 2);
    });
});

describe('sorting', function () {
    it('sorts by name ascending', function () {
        createTrail(['name' => 'Zebra Trail', 'slug' => 'zebra-trail']);
        createTrail(['name' => 'Alpha Trail', 'slug' => 'alpha-trail']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?sort=name&order=asc');

        $response->assertOk();
        $trails = $response->json('data.trails');
        expect($trails[0]['name'])->toBe('Alpha Trail');
        expect($trails[1]['name'])->toBe('Zebra Trail');
    });

    it('defaults to created_at desc', function () {
        $older = createTrail(['created_at' => now()->subDay()]);
        $newer = createTrail(['created_at' => now()]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails');

        $response->assertOk();
        $trails = $response->json('data.trails');
        expect($trails[0]['id'])->toBe($newer->id);
    });

    it('falls back to default sort for invalid column', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?sort=invalid_column');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    });

    it('sorts by distance_km', function () {
        createTrail(['distance_km' => 50.00]);
        createTrail(['distance_km' => 5.00]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?sort=distance_km&order=asc');

        $response->assertOk();
        $trails = $response->json('data.trails');
        expect((float) $trails[0]['distance_km'])->toBeLessThan((float) $trails[1]['distance_km']);
    });

    it('sorts by duration_hours', function () {
        createTrail(['duration_hours' => 8.0]);
        createTrail(['duration_hours' => 2.0]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?sort=duration_hours&order=asc');

        $response->assertOk();
        $trails = $response->json('data.trails');
        expect((float) $trails[0]['duration_hours'])->toBeLessThan((float) $trails[1]['duration_hours']);
    });
});

describe('search', function () {
    it('searches by name', function () {
        createTrail(['name' => 'Chania Falls Trail', 'slug' => 'chania-falls']);
        createTrail(['name' => 'Ngong Hills Traverse', 'slug' => 'ngong-hills']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?search=Chania');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('searches by location_name', function () {
        createTrail(['location_name' => 'Aberdare National Park']);
        createTrail(['location_name' => 'Nairobi City']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?search=Aberdare');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('searches by county', function () {
        createTrail(['county' => 'nyeri']);
        createTrail(['county' => 'nairobi']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?search=nyeri');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('returns empty for no match', function () {
        foreach (range(1, 3) as $i) {
            createTrail();
        }

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?search=nonexistenttrail');

        $response->assertOk()
            ->assertJsonCount(0, 'data.trails');
    });
});

describe('status filter', function () {
    it('filters by single status', function () {
        createTrail();
        createTrail();
        createTrail(['status' => 'published', 'published_at' => now()]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?status=published');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by comma-separated statuses', function () {
        createTrail(); // draft
        createTrail(['status' => 'published', 'published_at' => now()]);
        createTrail(['status' => 'archived']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?status=published,archived');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });
});

describe('difficulty filter', function () {
    it('filters by single difficulty', function () {
        createTrail(['difficulty' => 'easy']);
        createTrail(['difficulty' => 'expert']);
        createTrail(['difficulty' => 'expert']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?difficulty=expert');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });

    it('filters by comma-separated difficulties', function () {
        createTrail(['difficulty' => 'easy']);
        createTrail(['difficulty' => 'moderate']);
        createTrail(['difficulty' => 'expert']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?difficulty=easy,moderate');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });
});

describe('county filter', function () {
    it('filters by single county', function () {
        createTrail(['county' => 'nyeri']);
        createTrail(['county' => 'nairobi']);
        createTrail(['county' => 'nyeri']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?county=nyeri');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });

    it('filters by comma-separated counties', function () {
        createTrail(['county' => 'nyeri']);
        createTrail(['county' => 'nairobi']);
        createTrail(['county' => 'kiambu']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?county=nyeri,nairobi');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });
});

describe('created_by filter', function () {
    it('filters by creator', function () {
        $creator = User::factory()->create();
        createTrail(['created_by' => $creator->id]);
        createTrail();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails?created_by={$creator->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });
});

describe('date range filter', function () {
    it('filters by created_after', function () {
        createTrail(['created_at' => now()->subDays(10)]);
        createTrail(['created_at' => now()->subDay()]);

        $date = now()->subDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails?created_after={$date}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by created_before', function () {
        createTrail(['created_at' => now()->subDays(10)]);
        createTrail(['created_at' => now()->subDay()]);

        $date = now()->subDays(5)->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails?created_before={$date}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });
});

describe('trashed filter', function () {
    it('includes trashed with "with" value when user has permission', function () {
        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin = User::factory()->withRole($superAdminRole)->create();

        createTrail();
        $trashedTrail = createTrail();
        $trashedTrail->delete();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/trails?trashed=with');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });

    it('shows only trashed with "only" value when user has permission', function () {
        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin = User::factory()->withRole($superAdminRole)->create();

        createTrail();
        $trashedTrail = createTrail();
        $trashedTrail->delete();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/trails?trashed=only');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    });

    it('ignores trashed filter by default', function () {
        createTrail();
        $trashedTrail = createTrail();
        $trashedTrail->delete();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    });
});

describe('amenities filter', function () {
    it('filters trails having ALL specified amenities', function () {
        $amenity1 = Amenity::factory()->withName('Waterfall')->create();
        $amenity2 = Amenity::factory()->withName('Camping')->create();

        $trailWithBoth = createTrail();
        $trailWithBoth->amenities()->attach([$amenity1->id, $amenity2->id]);

        $trailWithOne = createTrail();
        $trailWithOne->amenities()->attach([$amenity1->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails?amenities={$amenity1->id},{$amenity2->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters trails having ANY specified amenities', function () {
        $amenity1 = Amenity::factory()->withName('Waterfall')->create();
        $amenity2 = Amenity::factory()->withName('Camping')->create();
        $amenity3 = Amenity::factory()->withName('Swimming')->create();

        $trail1 = createTrail();
        $trail1->amenities()->attach([$amenity1->id]);

        $trail2 = createTrail();
        $trail2->amenities()->attach([$amenity2->id]);

        $trail3 = createTrail();
        $trail3->amenities()->attach([$amenity3->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails?amenities_any={$amenity1->id},{$amenity2->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });

    it('returns empty when no trails match amenities', function () {
        $amenity = Amenity::factory()->withName('Waterfall')->create();
        createTrail();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/trails?amenities={$amenity->id}");

        $response->assertOk()
            ->assertJsonCount(0, 'data.trails');
    });
});

describe('distance range filter', function () {
    it('filters by min_distance', function () {
        createTrail(['distance_km' => 5.00]);
        createTrail(['distance_km' => 20.00]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?min_distance=10');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by max_distance', function () {
        createTrail(['distance_km' => 5.00]);
        createTrail(['distance_km' => 20.00]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?max_distance=10');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by distance range', function () {
        createTrail(['distance_km' => 5.00]);
        createTrail(['distance_km' => 15.00]);
        createTrail(['distance_km' => 30.00]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?min_distance=10&max_distance=20');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });
});

describe('duration range filter', function () {
    it('filters by min_duration', function () {
        createTrail(['duration_hours' => 2.0]);
        createTrail(['duration_hours' => 6.0]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?min_duration=4');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters by max_duration', function () {
        createTrail(['duration_hours' => 2.0]);
        createTrail(['duration_hours' => 6.0]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?max_duration=4');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });
});

describe('combined filters', function () {
    it('applies multiple filters simultaneously', function () {
        createTrail(['status' => 'published', 'published_at' => now(), 'difficulty' => 'easy', 'county' => 'nyeri']);
        createTrail(['status' => 'published', 'published_at' => now(), 'difficulty' => 'easy', 'county' => 'nairobi']);
        createTrail(['status' => 'published', 'published_at' => now(), 'difficulty' => 'expert', 'county' => 'nyeri']);
        createTrail(['difficulty' => 'easy', 'county' => 'nyeri']); // draft

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?status=published&difficulty=easy&county=nyeri');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });
});

describe('validation', function () {
    it('rejects invalid sort order', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?order=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    });

    it('rejects max_distance less than min_distance', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/trails?min_distance=20&max_distance=10');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['max_distance']);
    });
});
