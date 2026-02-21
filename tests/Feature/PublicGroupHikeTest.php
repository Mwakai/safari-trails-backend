<?php

use App\Models\Company;
use App\Models\GroupHike;
use App\Models\Region;
use App\Models\Trail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('public group hike index', function () {
    it('returns only published upcoming hikes', function () {
        GroupHike::factory()->published()->upcoming()->count(3)->create();
        GroupHike::factory()->draft()->upcoming()->count(2)->create();
        GroupHike::factory()->published()->past()->count(1)->create();

        $response = $this->getJson('/api/public/group-hikes');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    });

    it('returns pagination metadata', function () {
        GroupHike::factory()->published()->upcoming()->count(5)->create();

        $response = $this->getJson('/api/public/group-hikes');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'group_hikes',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    });
});

describe('public group hike show', function () {
    it('returns a published hike by slug', function () {
        $hike = GroupHike::factory()->published()->upcoming()->create(['slug' => 'my-hike']);

        $response = $this->getJson('/api/public/group-hikes/my-hike');

        $response->assertOk()
            ->assertJsonPath('data.group_hike.slug', 'my-hike');
    });

    it('returns 404 for draft hike', function () {
        GroupHike::factory()->draft()->create(['slug' => 'draft-hike']);

        $response = $this->getJson('/api/public/group-hikes/draft-hike');

        $response->assertNotFound();
    });

    it('returns 404 for unknown slug', function () {
        $response = $this->getJson('/api/public/group-hikes/nonexistent');

        $response->assertNotFound();
    });
});

describe('featured group hikes', function () {
    it('returns only featured published upcoming hikes', function () {
        GroupHike::factory()->published()->upcoming()->featured()->count(3)->create();
        GroupHike::factory()->published()->upcoming()->count(4)->create();

        $response = $this->getJson('/api/public/group-hikes/featured');

        $response->assertOk()
            ->assertJsonCount(3, 'data.group_hikes');
    });

    it('limits to 6 hikes', function () {
        GroupHike::factory()->published()->upcoming()->featured()->count(10)->create();

        $response = $this->getJson('/api/public/group-hikes/featured');

        $response->assertOk()
            ->assertJsonCount(6, 'data.group_hikes');
    });
});

describe('this week group hikes', function () {
    it('returns hikes starting this week', function () {
        GroupHike::factory()->published()->create(['start_date' => today()->toDateString()]);
        GroupHike::factory()->published()->create(['start_date' => today()->addDays(3)->toDateString()]);
        GroupHike::factory()->published()->create(['start_date' => today()->addDays(10)->toDateString()]);

        $response = $this->getJson('/api/public/group-hikes/this-week');

        $response->assertOk()
            ->assertJsonCount(2, 'data.group_hikes');
    });
});

describe('hikes by company', function () {
    it('returns published upcoming hikes for a company', function () {
        $company = Company::factory()->create(['slug' => 'my-company']);
        GroupHike::factory()->published()->upcoming()->withCompany($company)->count(3)->create();
        GroupHike::factory()->published()->upcoming()->count(2)->create();

        $response = $this->getJson('/api/public/group-hikes/by-company/my-company');

        $response->assertOk()
            ->assertJsonCount(3, 'data.group_hikes');
    });

    it('returns 404 for inactive company', function () {
        Company::factory()->inactive()->create(['slug' => 'inactive-co']);

        $response = $this->getJson('/api/public/group-hikes/by-company/inactive-co');

        $response->assertNotFound();
    });
});

describe('hikes by trail', function () {
    it('returns published upcoming hikes for a trail', function () {
        $trail = Trail::factory()->create(['slug' => 'some-trail']);
        GroupHike::factory()->published()->upcoming()->withTrail($trail)->count(2)->create();
        GroupHike::factory()->published()->upcoming()->count(3)->create();

        $response = $this->getJson('/api/public/group-hikes/by-trail/some-trail');

        $response->assertOk()
            ->assertJsonCount(2, 'data.group_hikes');
    });
});

describe('public filters', function () {
    it('filters by region slug', function () {
        $region = Region::factory()->create(['slug' => 'central']);
        GroupHike::factory()->published()->upcoming()->count(2)->create(['region_id' => $region->id]);
        GroupHike::factory()->published()->upcoming()->count(3)->create();

        $response = $this->getJson('/api/public/group-hikes?region=central');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });

    it('filters by difficulty', function () {
        GroupHike::factory()->published()->upcoming()->count(2)->create(['difficulty' => 'easy']);
        GroupHike::factory()->published()->upcoming()->count(3)->create(['difficulty' => 'difficult']);

        $response = $this->getJson('/api/public/group-hikes?difficulty=easy');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });

    it('filters by company slug', function () {
        $company = Company::factory()->create(['slug' => 'test-co']);
        GroupHike::factory()->published()->upcoming()->withCompany($company)->count(2)->create();
        GroupHike::factory()->published()->upcoming()->count(3)->create();

        $response = $this->getJson('/api/public/group-hikes?company=test-co');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });

    it('filters by trail slug', function () {
        $trail = Trail::factory()->create(['slug' => 'test-trail']);
        GroupHike::factory()->published()->upcoming()->withTrail($trail)->count(2)->create();
        GroupHike::factory()->published()->upcoming()->count(3)->create();

        $response = $this->getJson('/api/public/group-hikes?trail=test-trail');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });

    it('filters free hikes', function () {
        GroupHike::factory()->published()->upcoming()->free()->count(2)->create();
        GroupHike::factory()->published()->upcoming()->withPrice(1500)->count(3)->create();

        $response = $this->getJson('/api/public/group-hikes?is_free=1');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });

    it('filters by min_price', function () {
        GroupHike::factory()->published()->upcoming()->withPrice(500)->count(2)->create();
        GroupHike::factory()->published()->upcoming()->withPrice(2000)->count(3)->create();

        $response = $this->getJson('/api/public/group-hikes?min_price=1000');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    });

    it('filters by max_price', function () {
        GroupHike::factory()->published()->upcoming()->withPrice(500)->count(2)->create();
        GroupHike::factory()->published()->upcoming()->withPrice(2000)->count(3)->create();

        $response = $this->getJson('/api/public/group-hikes?max_price=1000');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});

describe('public company show', function () {
    it('returns active company by slug', function () {
        Company::factory()->create(['slug' => 'active-co']);

        $response = $this->getJson('/api/public/companies/active-co');

        $response->assertOk()
            ->assertJsonPath('data.company.slug', 'active-co');
    });

    it('returns 404 for inactive company', function () {
        Company::factory()->inactive()->create(['slug' => 'inactive-co']);

        $response = $this->getJson('/api/public/companies/inactive-co');

        $response->assertNotFound();
    });
});
