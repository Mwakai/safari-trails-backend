<?php

use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
});

describe('pagination', function () {
    it('uses default per_page of 15', function () {
        User::factory()->count(20)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 15);
    });

    it('accepts custom per_page', function () {
        User::factory()->count(10)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?per_page=5');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 5);
    });

    it('rejects per_page over 100', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });
});

describe('sorting', function () {
    it('sorts by first_name ascending', function () {
        User::factory()->create(['first_name' => 'Zara']);
        User::factory()->create(['first_name' => 'Alice']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?sort=first_name&order=asc');

        $response->assertOk();
        $users = $response->json('data.users');
        expect($users[0]['first_name'])->toBe('Alice');
    });

    it('defaults to created_at desc', function () {
        $older = User::factory()->create(['created_at' => now()->subDay()]);
        $newer = User::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users');

        $response->assertOk();
        $users = $response->json('data.users');
        // The admin user is newest, then $newer, then $older
        expect($users[0]['id'])->toBe($this->admin->id);
    });

    it('rejects invalid sort column', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?sort=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    });
});

describe('search', function () {
    it('searches by first_name', function () {
        User::factory()->create(['first_name' => 'John']);
        User::factory()->create(['first_name' => 'Jane']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?search=John');

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });

    it('searches by last_name', function () {
        User::factory()->create(['last_name' => 'Smith']);
        User::factory()->create(['last_name' => 'Doe']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?search=Smith');

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });

    it('searches by email', function () {
        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@other.com']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?search=john@example');

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });
});

describe('role_id filter', function () {
    it('filters by role_id', function () {
        $contentManagerRole = Role::factory()->contentManager()->create();
        User::factory()->withRole($contentManagerRole)->create();
        User::factory()->create(); // default role

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users?role_id={$contentManagerRole->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });
});

describe('status filter', function () {
    it('filters by status', function () {
        User::factory()->create(['status' => UserStatus::Active]);
        User::factory()->inactive()->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users?status=inactive');

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });
});

describe('company_id filter', function () {
    it('filters by company_id', function () {
        $company = Company::factory()->create();
        User::factory()->forCompany($company)->create();
        User::factory()->create(); // no company

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users?company_id={$company->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });
});

describe('created_by filter', function () {
    it('filters by created_by', function () {
        User::factory()->create(['created_by' => $this->admin->id]);
        User::factory()->create(['created_by' => null]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users?created_by={$this->admin->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });
});

describe('date range filter', function () {
    it('filters by created_after and created_before', function () {
        User::factory()->create(['created_at' => now()->subDays(10)]);
        User::factory()->create(['created_at' => now()->subDays(3)]);
        User::factory()->create(['created_at' => now()]);

        $after = now()->subDays(5)->format('Y-m-d');
        $before = now()->subDay()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users?created_after={$after}&created_before={$before}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });
});

describe('trashed filter', function () {
    it('includes trashed users with "with" when user has permission', function () {
        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin = User::factory()->withRole($superAdminRole)->create();

        $trashedUser = User::factory()->create();
        $trashedUser->delete();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users?trashed=with');

        $response->assertOk();
        $total = $response->json('data.meta.total');
        // superAdmin + trashedUser = at least 2
        expect($total)->toBeGreaterThanOrEqual(2);
    });
});

describe('response format', function () {
    it('uses ApiResponses format', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'users',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
                'message',
                'status',
            ]);
    });
});
