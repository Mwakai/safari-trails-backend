<?php

use App\Enums\TrailDifficulty;
use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\Media;
use App\Models\Role;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::factory()->superAdmin()->create();
    $this->adminRole = Role::factory()->admin()->create();
    $this->contentManagerRole = Role::factory()->contentManager()->create();
    $this->groupHikeOrganizerRole = Role::factory()->groupHikeOrganizer()->create();
});

function validTrailPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Chania Falls Trail',
        'slug' => 'chania-falls-trail',
        'description' => '<p>A beautiful trail through the Aberdare forest.</p>',
        'short_description' => 'A scenic trail to Chania Falls',
        'difficulty' => 'moderate',
        'distance_km' => 12.50,
        'duration_hours' => 5.5,
        'elevation_gain_m' => 450,
        'max_altitude_m' => 2800,
        'latitude' => -0.38340000,
        'longitude' => 36.96120000,
        'location_name' => 'Aberdare National Park',
        'county' => 'nyeri',
    ], $overrides);
}

describe('list trails', function () {
    it('allows users with trails.view permission to list trails', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/trails');

        $response->assertOk()
            ->assertJsonCount(3, 'data.trails');
    });

    it('returns pagination metadata', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/trails');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'trails',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ])
            ->assertJsonPath('data.meta.total', 3);
    });

    it('filters trails by status', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->count(2)->create();
        Trail::factory()->published()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/trails?status=published');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('filters trails by difficulty', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->withDifficulty(TrailDifficulty::Easy)->create();
        Trail::factory()->withDifficulty(TrailDifficulty::Expert)->create();
        Trail::factory()->withDifficulty(TrailDifficulty::Expert)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/trails?difficulty=expert');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });

    it('filters trails by county', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->withCounty('nyeri')->create();
        Trail::factory()->withCounty('nairobi')->create();
        Trail::factory()->withCounty('nyeri')->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/trails?county=nyeri');

        $response->assertOk()
            ->assertJsonCount(2, 'data.trails');
    });

    it('searches trails by name', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->withName('Chania Falls Trail')->create();
        Trail::factory()->withName('Ngong Hills Traverse')->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/trails?search=Chania');

        $response->assertOk()
            ->assertJsonCount(1, 'data.trails');
    });

    it('denies users without trails.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/trails');

        $response->assertForbidden();
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/admin/trails');

        $response->assertUnauthorized();
    });
});

describe('create trail', function () {
    it('allows users with trails.create permission to create a trail', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($contentManager)
            ->postJson('/api/admin/trails', validTrailPayload());

        $response->assertCreated()
            ->assertJsonPath('data.trail.name', 'Chania Falls Trail')
            ->assertJsonPath('data.trail.slug', 'chania-falls-trail')
            ->assertJsonPath('data.trail.difficulty', 'moderate')
            ->assertJsonPath('data.trail.status', 'draft')
            ->assertJsonPath('data.trail.county', 'nyeri');

        $this->assertDatabaseHas('trails', [
            'name' => 'Chania Falls Trail',
            'slug' => 'chania-falls-trail',
            'created_by' => $contentManager->id,
        ]);
    });

    it('creates trail with amenities', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $amenities = Amenity::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload([
                'amenity_ids' => $amenities->pluck('id')->toArray(),
            ]));

        $response->assertCreated();

        foreach ($amenities as $amenity) {
            $this->assertDatabaseHas('trail_amenity', [
                'trail_id' => $response->json('data.trail.id'),
                'amenity_id' => $amenity->id,
            ]);
        }
    });

    it('creates trail with images and gpx files', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $galleryMedia = Media::factory()->create();
        $gpxMedia = Media::factory()->document()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload([
                'images' => [
                    [
                        'media_id' => $galleryMedia->id,
                        'type' => 'gallery',
                        'caption' => 'Trail entrance',
                        'sort_order' => 0,
                    ],
                ],
                'gpx_files' => [
                    [
                        'media_id' => $gpxMedia->id,
                        'name' => 'Full Route',
                        'sort_order' => 0,
                    ],
                ],
            ]));

        $response->assertCreated();

        $trailId = $response->json('data.trail.id');

        $this->assertDatabaseHas('trail_images', [
            'trail_id' => $trailId,
            'media_id' => $galleryMedia->id,
            'type' => 'gallery',
            'caption' => 'Trail entrance',
        ]);

        $this->assertDatabaseHas('trail_gpx', [
            'trail_id' => $trailId,
            'media_id' => $gpxMedia->id,
            'name' => 'Full Route',
        ]);
    });

    it('sets default status to draft', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload());

        $response->assertCreated()
            ->assertJsonPath('data.trail.status', 'draft');
    });

    it('sets created_by to authenticated user', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($contentManager)
            ->postJson('/api/admin/trails', validTrailPayload());

        $response->assertCreated();

        $this->assertDatabaseHas('trails', [
            'id' => $response->json('data.trail.id'),
            'created_by' => $contentManager->id,
        ]);
    });

    it('denies users without trails.create permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['trails.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/admin/trails', validTrailPayload());

        $response->assertForbidden();
    });

    it('validates required fields', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name', 'slug', 'description', 'difficulty',
                'distance_km', 'duration_hours', 'latitude',
                'longitude', 'location_name', 'county',
            ]);
    });

    it('validates slug uniqueness', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->create(['slug' => 'chania-falls-trail']);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    it('validates county is a valid Kenyan county', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload([
                'county' => 'InvalidCounty',
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['county']);
    });

    it('validates difficulty is a valid enum value', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload([
                'difficulty' => 'extreme',
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['difficulty']);
    });

    it('validates latitude and longitude ranges', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload([
                'latitude' => 91,
                'longitude' => 181,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    });

    it('validates amenity_ids exist', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload([
                'amenity_ids' => [999],
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amenity_ids.0']);
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->postJson('/api/admin/trails', validTrailPayload());

        $response->assertUnauthorized();
    });
});

describe('show trail', function () {
    it('allows users with trails.view permission to view a trail with all relations', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        $amenities = Amenity::factory()->count(2)->create();
        $trail->amenities()->attach($amenities->pluck('id'));

        $media = Media::factory()->create();
        $trail->images()->create([
            'media_id' => $media->id,
            'type' => 'gallery',
            'caption' => 'Test image',
            'sort_order' => 0,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/trails/{$trail->id}");

        $response->assertOk()
            ->assertJsonPath('data.trail.name', $trail->name)
            ->assertJsonCount(2, 'data.trail.amenities')
            ->assertJsonCount(1, 'data.trail.images');
    });

    it('denies users without trails.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/admin/trails/{$trail->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/trails/999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $trail = Trail::factory()->create();

        $response = $this->getJson("/api/admin/trails/{$trail->id}");

        $response->assertUnauthorized();
    });
});

describe('update trail', function () {
    it('allows users with trails.update permission to update a trail', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($contentManager)
            ->putJson("/api/admin/trails/{$trail->id}", [
                'name' => 'Updated Trail Name',
                'slug' => 'updated-trail-name',
                'description' => 'Updated description',
                'difficulty' => 'expert',
                'distance_km' => 20.00,
                'duration_hours' => 8.0,
                'latitude' => -1.28640000,
                'longitude' => 36.81720000,
                'location_name' => 'Updated Location',
                'county' => 'nairobi',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.trail.name', 'Updated Trail Name')
            ->assertJsonPath('data.trail.difficulty', 'expert');

        $this->assertDatabaseHas('trails', [
            'id' => $trail->id,
            'name' => 'Updated Trail Name',
            'updated_by' => $contentManager->id,
        ]);
    });

    it('allows partial updates using PATCH', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create([
            'name' => 'Original Name',
            'short_description' => 'Original description',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'short_description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.trail.name', 'Original Name')
            ->assertJsonPath('data.trail.short_description', 'Updated description');
    });

    it('syncs amenities on update', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        $oldAmenities = Amenity::factory()->count(2)->create();
        $trail->amenities()->attach($oldAmenities->pluck('id'));

        $newAmenities = Amenity::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'amenity_ids' => $newAmenities->pluck('id')->toArray(),
            ]);

        $response->assertOk();

        foreach ($oldAmenities as $amenity) {
            $this->assertDatabaseMissing('trail_amenity', [
                'trail_id' => $trail->id,
                'amenity_id' => $amenity->id,
            ]);
        }

        foreach ($newAmenities as $amenity) {
            $this->assertDatabaseHas('trail_amenity', [
                'trail_id' => $trail->id,
                'amenity_id' => $amenity->id,
            ]);
        }
    });

    it('syncs images on update', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        $oldMedia = Media::factory()->create();
        $trail->images()->create([
            'media_id' => $oldMedia->id,
            'type' => 'gallery',
            'sort_order' => 0,
            'created_at' => now(),
        ]);

        $newMedia = Media::factory()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'images' => [
                    [
                        'media_id' => $newMedia->id,
                        'type' => 'gallery',
                        'caption' => 'New image',
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseMissing('trail_images', [
            'trail_id' => $trail->id,
            'media_id' => $oldMedia->id,
        ]);

        $this->assertDatabaseHas('trail_images', [
            'trail_id' => $trail->id,
            'media_id' => $newMedia->id,
            'caption' => 'New image',
        ]);
    });

    it('syncs gpx files on update', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        $newMedia = Media::factory()->document()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'gpx_files' => [
                    [
                        'media_id' => $newMedia->id,
                        'name' => 'Updated Route',
                        'sort_order' => 0,
                    ],
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('trail_gpx', [
            'trail_id' => $trail->id,
            'media_id' => $newMedia->id,
            'name' => 'Updated Route',
        ]);
    });

    it('sets updated_by to authenticated user', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'short_description' => 'Updated',
            ]);

        $this->assertDatabaseHas('trails', [
            'id' => $trail->id,
            'updated_by' => $admin->id,
        ]);
    });

    it('validates slug uniqueness excluding current trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Trail::factory()->create(['slug' => 'existing-slug']);
        $trail = Trail::factory()->create(['slug' => 'my-trail']);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/trails/{$trail->id}", [
                'slug' => 'existing-slug',
                'name' => 'My Trail',
                'description' => 'Test',
                'difficulty' => 'easy',
                'distance_km' => 5,
                'duration_hours' => 2,
                'latitude' => -1.28,
                'longitude' => 36.81,
                'location_name' => 'Test',
                'county' => 'nairobi',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    });

    it('allows updating slug to the same value', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create(['slug' => 'my-trail']);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'slug' => 'my-trail',
            ]);

        $response->assertOk();
    });

    it('denies users without trails.update permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['trails.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/admin/trails/{$trail->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    });

    it('returns 404 for non-existent trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->putJson('/api/admin/trails/999', [
                'name' => 'Updated Name',
            ]);

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $trail = Trail::factory()->create();

        $response = $this->putJson("/api/admin/trails/{$trail->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertUnauthorized();
    });
});

describe('update trail status', function () {
    it('allows users with trails.update permission to change status', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}/status", [
                'status' => 'published',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.trail.status', 'published');
    });

    it('sets published_at when publishing for the first time', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        expect($trail->published_at)->toBeNull();

        $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}/status", [
                'status' => 'published',
            ]);

        $trail->refresh();

        expect($trail->published_at)->not->toBeNull();
    });

    it('preserves published_at when re-publishing after archiving', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->published()->create();
        $originalPublishedAt = $trail->published_at->toDateTimeString();

        $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}/status", [
                'status' => 'archived',
            ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}/status", [
                'status' => 'published',
            ]);

        $trail->refresh();

        expect($trail->published_at->toDateTimeString())->toBe($originalPublishedAt);
    });

    it('denies users without trails.update permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['trails.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson("/api/admin/trails/{$trail->id}/status", [
                'status' => 'published',
            ]);

        $response->assertForbidden();
    });

    it('validates status is a valid enum value', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}/status", [
                'status' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('returns 404 for non-existent trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->patchJson('/api/admin/trails/999/status', [
                'status' => 'published',
            ]);

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $trail = Trail::factory()->create();

        $response = $this->patchJson("/api/admin/trails/{$trail->id}/status", [
            'status' => 'published',
        ]);

        $response->assertUnauthorized();
    });
});

describe('delete trail', function () {
    it('allows users with trails.delete permission to soft delete a trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/trails/{$trail->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Trail deleted successfully');

        $this->assertSoftDeleted('trails', ['id' => $trail->id]);
    });

    it('denies users without trails.delete permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['trails.view', 'trails.update']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/admin/trails/{$trail->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/trails/999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $trail = Trail::factory()->create();

        $response = $this->deleteJson("/api/admin/trails/{$trail->id}");

        $response->assertUnauthorized();
    });
});

describe('restore trail', function () {
    it('allows users with trails.update permission to restore a soft deleted trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        $trail->delete();

        $this->assertSoftDeleted('trails', ['id' => $trail->id]);

        $response = $this->actingAs($admin)
            ->postJson("/api/admin/trails/{$trail->id}/restore");

        $response->assertOk()
            ->assertJsonPath('message', 'Trail restored successfully');

        $this->assertDatabaseHas('trails', [
            'id' => $trail->id,
            'deleted_at' => null,
        ]);
    });

    it('denies users without trails.update permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['trails.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $trail = Trail::factory()->create();
        $trail->delete();

        $response = $this->actingAs($user)
            ->postJson("/api/admin/trails/{$trail->id}/restore");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/trails/999/restore');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $trail = Trail::factory()->create();
        $trail->delete();

        $response = $this->postJson("/api/admin/trails/{$trail->id}/restore");

        $response->assertUnauthorized();
    });
});

describe('activity logging', function () {
    it('logs trail created event', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload());

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'trails',
            'event' => 'created',
            'subject_type' => Trail::class,
            'causer_type' => User::class,
            'causer_id' => $admin->id,
        ]);
    });

    it('logs trail updated event', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'short_description' => 'Updated',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'trails',
            'event' => 'updated',
            'subject_type' => Trail::class,
            'subject_id' => $trail->id,
            'causer_id' => $admin->id,
        ]);
    });

    it('logs trail status changed event with old and new status', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}/status", [
                'status' => 'published',
            ]);

        $log = ActivityLog::where('event', 'status_changed')
            ->where('subject_id', $trail->id)
            ->first();

        expect($log)->not->toBeNull();
        expect($log->properties)->toBe([
            'old_status' => 'draft',
            'new_status' => 'published',
        ]);
        expect($log->causer_id)->toBe($admin->id);
    });

    it('logs trail deleted event', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/trails/{$trail->id}");

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'trails',
            'event' => 'deleted',
            'subject_type' => Trail::class,
            'subject_id' => $trail->id,
            'causer_id' => $admin->id,
        ]);
    });

    it('logs trail restored event', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        $trail->delete();

        $this->actingAs($admin)
            ->postJson("/api/admin/trails/{$trail->id}/restore");

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'trails',
            'event' => 'restored',
            'subject_type' => Trail::class,
            'subject_id' => $trail->id,
            'causer_id' => $admin->id,
        ]);
    });
});

describe('cache invalidation', function () {
    it('clears trail caches when a trail is created', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Cache::put('trails.public', 'cached-data');

        $this->actingAs($admin)
            ->postJson('/api/admin/trails', validTrailPayload());

        expect(Cache::has('trails.public'))->toBeFalse();
    });

    it('clears trail caches when a trail is updated', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        Cache::put("trail.{$trail->id}", 'cached-data');
        Cache::put('trails.public', 'cached-data');

        $this->actingAs($admin)
            ->patchJson("/api/admin/trails/{$trail->id}", [
                'short_description' => 'Updated',
            ]);

        expect(Cache::has("trail.{$trail->id}"))->toBeFalse();
        expect(Cache::has('trails.public'))->toBeFalse();
    });

    it('clears trail caches when a trail is deleted', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();
        Cache::put("trail.{$trail->id}", 'cached-data');
        Cache::put('trails.public', 'cached-data');

        $this->actingAs($admin)
            ->deleteJson("/api/admin/trails/{$trail->id}");

        expect(Cache::has("trail.{$trail->id}"))->toBeFalse();
        expect(Cache::has('trails.public'))->toBeFalse();
    });
});

describe('counties endpoint', function () {
    it('returns counties grouped by popular and other', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/counties');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'counties' => ['popular', 'other', 'all'],
                ],
            ]);

        expect($response->json('data.counties.popular.nyeri'))->toBe('Nyeri');
        expect($response->json('data.counties.other.mombasa'))->toBe('Mombasa');
    });

    it('caches the counties response', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $this->actingAs($admin)->getJson('/api/admin/counties');

        expect(Cache::has('trails.counties'))->toBeTrue();
    });
});

describe('difficulties endpoint', function () {
    it('returns all difficulty levels', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/difficulties');

        $response->assertOk()
            ->assertJsonCount(4, 'data.difficulties');

        $values = collect($response->json('data.difficulties'))->pluck('value')->all();

        expect($values)->toContain('easy', 'moderate', 'difficult', 'expert');
    });

    it('caches the difficulties response', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $this->actingAs($admin)->getJson('/api/admin/difficulties');

        expect(Cache::has('trails.difficulties'))->toBeTrue();
    });
});
