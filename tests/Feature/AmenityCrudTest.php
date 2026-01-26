<?php

use App\Models\Amenity;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::factory()->superAdmin()->create();
    $this->adminRole = Role::factory()->admin()->create();
    $this->contentManagerRole = Role::factory()->contentManager()->create();
    $this->groupHikeOrganizerRole = Role::factory()->groupHikeOrganizer()->create();
});

describe('list amenities', function () {
    it('allows users with amenities.view permission to list amenities', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Amenity::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/amenities');

        $response->assertOk()
            ->assertJsonCount(3, 'data.amenities');
    });

    it('returns amenities ordered by name', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();
        Amenity::factory()->withName('Waterfall')->create();
        Amenity::factory()->withName('Camping')->create();
        Amenity::factory()->withName('Bird Watching')->create();

        $response = $this->actingAs($contentManager)
            ->getJson('/api/admin/amenities');

        $response->assertOk();
        $amenities = $response->json('data.amenities');
        expect($amenities[0]['name'])->toBe('Bird Watching');
        expect($amenities[1]['name'])->toBe('Camping');
        expect($amenities[2]['name'])->toBe('Waterfall');
    });

    it('denies users without amenities.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/amenities');

        $response->assertForbidden();
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/admin/amenities');

        $response->assertUnauthorized();
    });
});

describe('create amenity', function () {
    it('allows users with amenities.create permission to create amenity', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($contentManager)
            ->postJson('/api/admin/amenities', [
                'name' => 'Waterfall',
                'slug' => 'waterfall',
                'icon' => 'waterfall-icon',
                'description' => 'A beautiful waterfall on the trail',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.amenity.name', 'Waterfall')
            ->assertJsonPath('data.amenity.slug', 'waterfall')
            ->assertJsonPath('data.amenity.icon', 'waterfall-icon')
            ->assertJsonPath('data.amenity.is_active', true);

        $this->assertDatabaseHas('amenities', [
            'name' => 'Waterfall',
            'slug' => 'waterfall',
            'created_by' => $contentManager->id,
        ]);
    });

    it('sets default is_active to true when not provided', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/amenities', [
                'name' => 'Camping',
                'slug' => 'camping',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.amenity.is_active', true);
    });

    it('allows setting is_active to false', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/amenities', [
                'name' => 'Camping',
                'slug' => 'camping',
                'is_active' => false,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.amenity.is_active', false);
    });

    it('denies users without amenities.create permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['amenities.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/admin/amenities', [
                'name' => 'Waterfall',
                'slug' => 'waterfall',
            ]);

        $response->assertForbidden();
    });

    it('validates required fields', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/amenities', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug']);
    });

    it('validates slug uniqueness', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Amenity::factory()->create(['slug' => 'waterfall']);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/amenities', [
                'name' => 'Waterfall',
                'slug' => 'waterfall',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->postJson('/api/admin/amenities', [
            'name' => 'Waterfall',
            'slug' => 'waterfall',
        ]);

        $response->assertUnauthorized();
    });
});

describe('show amenity', function () {
    it('allows users with amenities.view permission to view amenity', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $amenity = Amenity::factory()->create(['name' => 'Waterfall']);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/amenities/{$amenity->id}");

        $response->assertOk()
            ->assertJsonPath('data.amenity.name', 'Waterfall');
    });

    it('denies users without amenities.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $amenity = Amenity::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/admin/amenities/{$amenity->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent amenity', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/amenities/999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $amenity = Amenity::factory()->create();

        $response = $this->getJson("/api/admin/amenities/{$amenity->id}");

        $response->assertUnauthorized();
    });
});

describe('update amenity', function () {
    it('allows users with amenities.update permission to update amenity', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();
        $amenity = Amenity::factory()->create(['name' => 'Waterfall']);

        $response = $this->actingAs($contentManager)
            ->putJson("/api/admin/amenities/{$amenity->id}", [
                'name' => 'Scenic Waterfall',
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.amenity.name', 'Scenic Waterfall')
            ->assertJsonPath('data.amenity.description', 'Updated description');
    });

    it('allows partial updates using PATCH', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $amenity = Amenity::factory()->create([
            'name' => 'Waterfall',
            'description' => 'Original description',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/amenities/{$amenity->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.amenity.name', 'Waterfall')
            ->assertJsonPath('data.amenity.description', 'Updated description');
    });

    it('validates slug uniqueness excluding current amenity', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Amenity::factory()->create(['slug' => 'camping']);
        $amenity = Amenity::factory()->create(['slug' => 'waterfall']);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/amenities/{$amenity->id}", [
                'slug' => 'camping',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    it('allows updating slug to the same value', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $amenity = Amenity::factory()->create(['slug' => 'waterfall']);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/amenities/{$amenity->id}", [
                'slug' => 'waterfall',
            ]);

        $response->assertOk();
    });

    it('allows updating is_active status', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $amenity = Amenity::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/amenities/{$amenity->id}", [
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.amenity.is_active', false);
    });

    it('denies users without amenities.update permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['amenities.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $amenity = Amenity::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/admin/amenities/{$amenity->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    });

    it('returns 404 for non-existent amenity', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/amenities/999', [
                'name' => 'Updated Name',
            ]);

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $amenity = Amenity::factory()->create();

        $response = $this->putJson("/api/admin/amenities/{$amenity->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertUnauthorized();
    });
});

describe('delete amenity', function () {
    it('allows users with amenities.delete permission to delete amenity', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $amenity = Amenity::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/amenities/{$amenity->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Amenity deleted successfully');

        $this->assertDatabaseMissing('amenities', ['id' => $amenity->id]);
    });

    it('denies users without amenities.delete permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['amenities.view', 'amenities.update']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $amenity = Amenity::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/admin/amenities/{$amenity->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent amenity', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/amenities/999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $amenity = Amenity::factory()->create();

        $response = $this->deleteJson("/api/admin/amenities/{$amenity->id}");

        $response->assertUnauthorized();
    });
});
