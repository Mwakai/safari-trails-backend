<?php

use App\Models\Company;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->organizerRole = Role::factory()->groupHikeOrganizer()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
    $this->organizer = User::factory()->withRole($this->organizerRole)->create();
});

describe('list companies', function () {
    it('admin can list companies', function () {
        Company::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/companies');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 5);
    });

    it('organizer can list companies', function () {
        Company::factory()->count(3)->create();

        $response = $this->actingAs($this->organizer)
            ->getJson('/api/admin/companies');

        $response->assertOk();
    });

    it('unauthenticated users get 401', function () {
        $response = $this->getJson('/api/admin/companies');
        $response->assertUnauthorized();
    });
});

describe('show company', function () {
    it('admin can view a company', function () {
        $company = Company::factory()->create(['name' => 'Peak Adventures']);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/companies/{$company->id}");

        $response->assertOk()
            ->assertJsonPath('data.company.name', 'Peak Adventures');
    });

    it('returns new fields', function () {
        $company = Company::factory()->verified()->create([
            'website' => 'https://example.com',
            'email' => 'info@example.com',
            'phone' => '+254 700 000000',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/companies/{$company->id}");

        $response->assertOk()
            ->assertJsonPath('data.company.website', 'https://example.com')
            ->assertJsonPath('data.company.email', 'info@example.com')
            ->assertJsonPath('data.company.is_verified', true)
            ->assertJsonPath('data.company.is_active', true);
    });
});

describe('create company', function () {
    it('admin can create a company', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/companies', [
                'name' => 'Summit Explorers',
                'description' => 'We explore summits.',
                'email' => 'info@summit.co.ke',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.company.name', 'Summit Explorers');
    });

    it('auto-generates slug from name', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/companies', [
                'name' => 'Wild Peaks Kenya',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.company.slug', 'wild-peaks-kenya');
    });

    it('validates required name', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/companies', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('validates website is a valid URL', function () {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/companies', [
                'name' => 'Test Co',
                'website' => 'not-a-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    });

    it('organizer cannot create a company', function () {
        $response = $this->actingAs($this->organizer)
            ->postJson('/api/admin/companies', [
                'name' => 'My Company',
            ]);

        $response->assertForbidden();
    });
});

describe('update company', function () {
    it('admin can update a company', function () {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/companies/{$company->id}", [
                'name' => 'Updated Name',
                'is_verified' => true,
                'is_active' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.company.name', 'Updated Name')
            ->assertJsonPath('data.company.is_verified', true);
    });

    it('validates unique slug on update', function () {
        $company1 = Company::factory()->create(['slug' => 'slug-one']);
        $company2 = Company::factory()->create(['slug' => 'slug-two']);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/companies/{$company2->id}", [
                'slug' => 'slug-one',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    });

    it('allows updating own slug on update', function () {
        $company = Company::factory()->create(['slug' => 'my-slug']);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/companies/{$company->id}", [
                'slug' => 'my-slug',
                'name' => 'Updated Name',
            ]);

        $response->assertOk();
    });

    it('organizer cannot update a company', function () {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->organizer)
            ->patchJson("/api/admin/companies/{$company->id}", [
                'name' => 'Sneaky Update',
            ]);

        $response->assertForbidden();
    });
});

describe('delete company', function () {
    it('admin can delete a company', function () {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/companies/{$company->id}");

        $response->assertOk();
        $this->assertModelMissing($company);
    });

    it('organizer cannot delete a company', function () {
        $company = Company::factory()->create();

        $response = $this->actingAs($this->organizer)
            ->deleteJson("/api/admin/companies/{$company->id}");

        $response->assertForbidden();
    });
});

describe('company logo and cover image', function () {
    it('associates logo and cover image with company', function () {
        $logo = Media::factory()->create();
        $cover = Media::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/companies', [
                'name' => 'Media Rich Co',
                'logo_id' => $logo->id,
                'cover_image_id' => $cover->id,
            ]);

        $response->assertStatus(201);

        $company = Company::where('name', 'Media Rich Co')->first();
        $this->assertEquals($logo->id, $company->logo_id);
        $this->assertEquals($cover->id, $company->cover_image_id);
    });
});

describe('company is_active and is_verified', function () {
    it('company is active by default', function () {
        $company = Company::factory()->create();
        $this->assertTrue($company->isActive());
    });

    it('inactive company is not active', function () {
        $company = Company::factory()->inactive()->create();
        $this->assertFalse($company->isActive());
    });

    it('verified company has is_verified true', function () {
        $company = Company::factory()->verified()->create();
        $this->assertTrue($company->is_verified);
    });
});
