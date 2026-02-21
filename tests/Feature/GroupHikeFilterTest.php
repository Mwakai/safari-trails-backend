<?php

use App\Models\Company;
use App\Models\GroupHike;
use App\Models\Region;
use App\Models\Role;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->organizerRole = Role::factory()->groupHikeOrganizer()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
});

describe('status filter', function () {
    it('filters by single status', function () {
        GroupHike::factory()->published()->count(3)->create();
        GroupHike::factory()->draft()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?status=published');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    });

    it('filters by multiple comma-separated statuses', function () {
        GroupHike::factory()->published()->count(2)->create();
        GroupHike::factory()->cancelled()->count(1)->create();
        GroupHike::factory()->draft()->count(4)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?status=published,cancelled');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    });
});

describe('organizer_id filter (admin-only)', function () {
    it('admin can filter by organizer_id', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        GroupHike::factory()->count(3)->create(['organizer_id' => $organizer->id, 'created_by' => $organizer->id]);
        GroupHike::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/group-hikes?organizer_id={$organizer->id}");

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    });

    it('organizer cannot filter by organizer_id (stripped)', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        $other = User::factory()->withRole($this->organizerRole)->create();

        GroupHike::factory()->count(2)->create(['organizer_id' => $organizer->id, 'created_by' => $organizer->id]);
        GroupHike::factory()->count(3)->create(['organizer_id' => $other->id, 'created_by' => $other->id]);

        // Organizer passes other's ID - should be stripped, they only see own hikes
        $response = $this->actingAs($organizer)
            ->getJson("/api/admin/group-hikes?organizer_id={$other->id}");

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('company_id filter', function () {
    it('filters by company_id', function () {
        $company = Company::factory()->create();
        GroupHike::factory()->withCompany($company)->count(2)->create();
        GroupHike::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/group-hikes?company_id={$company->id}");

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('trail_id filter', function () {
    it('filters by trail_id', function () {
        $trail = Trail::factory()->create();
        GroupHike::factory()->withTrail($trail)->count(2)->create();
        GroupHike::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/group-hikes?trail_id={$trail->id}");

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('region_id filter', function () {
    it('filters by region_id', function () {
        $region = Region::factory()->create();
        GroupHike::factory()->count(2)->create(['region_id' => $region->id]);
        GroupHike::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/group-hikes?region_id={$region->id}");

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('date range filter', function () {
    it('filters by date_from', function () {
        GroupHike::factory()->create(['start_date' => '2026-03-01']);
        GroupHike::factory()->create(['start_date' => '2026-04-01']);
        GroupHike::factory()->create(['start_date' => '2026-05-01']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?date_from=2026-04-01');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });

    it('filters by date_to', function () {
        GroupHike::factory()->create(['start_date' => '2026-03-01']);
        GroupHike::factory()->create(['start_date' => '2026-04-01']);
        GroupHike::factory()->create(['start_date' => '2026-05-01']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?date_to=2026-04-01');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('is_featured filter', function () {
    it('filters by is_featured', function () {
        GroupHike::factory()->featured()->count(2)->create();
        GroupHike::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?is_featured=1');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('search filter', function () {
    it('searches by title', function () {
        GroupHike::factory()->create(['title' => 'Kilimanjaro Adventure']);
        GroupHike::factory()->create(['title' => 'Aberdare Forest Walk']);
        GroupHike::factory()->create(['title' => 'Mount Kenya Trek']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?search=kenya');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    });
});

describe('sorting', function () {
    it('sorts by start_date ascending by default', function () {
        GroupHike::factory()->create(['start_date' => '2026-05-01', 'title' => 'May Hike']);
        GroupHike::factory()->create(['start_date' => '2026-03-01', 'title' => 'March Hike']);
        GroupHike::factory()->create(['start_date' => '2026-04-01', 'title' => 'April Hike']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes');

        $response->assertOk();

        $titles = collect($response->json('data.group_hikes'))->pluck('title')->toArray();
        expect($titles)->toBe(['March Hike', 'April Hike', 'May Hike']);
    });

    it('sorts by title descending', function () {
        GroupHike::factory()->create(['title' => 'Zebra Hike', 'start_date' => '2026-04-01']);
        GroupHike::factory()->create(['title' => 'Apple Hike', 'start_date' => '2026-04-02']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?sort=title&order=desc');

        $response->assertOk();

        $titles = collect($response->json('data.group_hikes'))->pluck('title')->toArray();
        expect($titles[0])->toBe('Zebra Hike');
    });
});

describe('pagination', function () {
    it('respects per_page parameter', function () {
        GroupHike::factory()->count(20)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/group-hikes?per_page=5');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonCount(5, 'data.group_hikes');
    });
});
