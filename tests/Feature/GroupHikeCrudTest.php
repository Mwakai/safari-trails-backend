<?php

use App\Enums\GroupHikeStatus;
use App\Models\Company;
use App\Models\GroupHike;
use App\Models\Media;
use App\Models\Region;
use App\Models\Role;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->organizerRole = Role::factory()->groupHikeOrganizer()->create();
    $this->region = Region::factory()->create();
});

function validHikePayload(array $overrides = []): array
{
    static $region = null;

    if ($region === null) {
        $region = Region::first() ?? Region::factory()->create();
    }

    return array_merge([
        'title' => 'Mount Kenya Summit Hike',
        'description' => 'An epic summit hike to the top.',
        'start_date' => now()->addWeek()->toDateString(),
        'start_time' => '06:00',
        'custom_location_name' => 'Naro Moru Gate',
        'latitude' => -0.1,
        'longitude' => 37.1,
        'region_id' => $region->id,
        'difficulty' => 'difficult',
    ], $overrides);
}

describe('create group hike', function () {
    it('allows admin to create a hike with custom location', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/group-hikes', validHikePayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.group_hike.title', 'Mount Kenya Summit Hike')
            ->assertJsonPath('data.group_hike.status', 'draft');
    });

    it('allows admin to create a hike linked to a trail', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $trail = Trail::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/group-hikes', [
                'title' => 'Trail Group Hike',
                'description' => 'A hike on an existing trail.',
                'start_date' => now()->addWeek()->toDateString(),
                'start_time' => '07:00',
                'trail_id' => $trail->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.group_hike.trail_id', $trail->id);
    });

    it('fails validation when no trail and missing location fields', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/group-hikes', [
                'title' => 'Incomplete Hike',
                'description' => 'Missing location.',
                'start_date' => now()->addWeek()->toDateString(),
                'start_time' => '06:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['custom_location_name', 'latitude', 'longitude', 'region_id', 'difficulty']);
    });

    it('fails when start_date is in the past', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/group-hikes', validHikePayload([
                'start_date' => now()->subDay()->toDateString(),
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    });

    it('organizer cannot assign another companys hike', function () {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $organizer = User::factory()->withRole($this->organizerRole)->forCompany($company)->create();

        $response = $this->actingAs($organizer)
            ->postJson('/api/admin/group-hikes', validHikePayload([
                'company_id' => $otherCompany->id,
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_id']);
    });

    it('auto-assigns organizer_id to current user for organizer role', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();

        $response = $this->actingAs($organizer)
            ->postJson('/api/admin/group-hikes', validHikePayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.group_hike.organizer_id', $organizer->id);
    });
});

describe('show group hike', function () {
    it('admin sees any hike', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/group-hikes/{$hike->id}");

        $response->assertOk()
            ->assertJsonPath('data.group_hike.id', $hike->id);
    });

    it('organizer sees own hike', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        $hike = GroupHike::factory()->create(['organizer_id' => $organizer->id, 'created_by' => $organizer->id]);

        $response = $this->actingAs($organizer)
            ->getJson("/api/admin/group-hikes/{$hike->id}");

        $response->assertOk();
    });

    it('organizer cannot see another organizers hike', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        $other = User::factory()->withRole($this->organizerRole)->create();
        $hike = GroupHike::factory()->create(['organizer_id' => $other->id, 'created_by' => $other->id]);

        $response = $this->actingAs($organizer)
            ->getJson("/api/admin/group-hikes/{$hike->id}");

        $response->assertForbidden();
    });
});

describe('update group hike', function () {
    it('admin can update any hike', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.group_hike.title', 'Updated Title');
    });

    it('cannot update a cancelled hike', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->cancelled()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}", [
                'title' => 'Attempted Update',
            ]);

        $response->assertForbidden();
    });

    it('organizer cannot update another organizers hike', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        $other = User::factory()->withRole($this->organizerRole)->create();
        $hike = GroupHike::factory()->create(['organizer_id' => $other->id, 'created_by' => $other->id]);

        $response = $this->actingAs($organizer)
            ->patchJson("/api/admin/group-hikes/{$hike->id}", ['title' => 'Nope']);

        $response->assertForbidden();
    });
});

describe('publish group hike', function () {
    it('admin can publish a draft hike with featured image and future date', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $media = Media::factory()->create();
        $hike = GroupHike::factory()->create([
            'featured_image_id' => $media->id,
            'start_date' => now()->addWeek()->toDateString(),
            'custom_location_name' => 'Some Gate',
            'latitude' => -0.5,
            'longitude' => 37.0,
            'region_id' => $this->region->id,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/publish");

        $response->assertOk()
            ->assertJsonPath('data.group_hike.status', 'published');

        $this->assertNotNull($hike->fresh()->published_at);
    });

    it('fails to publish without featured image', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->create([
            'featured_image_id' => null,
            'start_date' => now()->addWeek()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/publish");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['featured_image_id']);
    });

    it('fails to publish when start_date is in the past', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $media = Media::factory()->create();
        $hike = GroupHike::factory()->past()->create([
            'featured_image_id' => $media->id,
            'custom_location_name' => 'Gate',
            'latitude' => -0.5,
            'longitude' => 37.0,
            'region_id' => $this->region->id,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/publish");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    });

    it('does not reset published_at on re-publish', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $media = Media::factory()->create();
        $originalPublishedAt = now()->subDays(3);
        $hike = GroupHike::factory()->create([
            'featured_image_id' => $media->id,
            'start_date' => now()->addWeek()->toDateString(),
            'status' => GroupHikeStatus::Draft,
            'published_at' => $originalPublishedAt,
            'custom_location_name' => 'Gate',
            'latitude' => -0.5,
            'longitude' => 37.0,
            'region_id' => $this->region->id,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/publish");

        $this->assertEquals(
            $originalPublishedAt->toDateString(),
            $hike->fresh()->published_at->toDateString()
        );
    });
});

describe('unpublish group hike', function () {
    it('admin can unpublish a published hike', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->published()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/unpublish");

        $response->assertOk()
            ->assertJsonPath('data.group_hike.status', 'draft');
    });
});

describe('cancel group hike', function () {
    it('admin can cancel a published hike', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->published()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/cancel", [
                'cancellation_reason' => 'Due to bad weather conditions',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.group_hike.status', 'cancelled')
            ->assertJsonPath('data.group_hike.cancellation_reason', 'Due to bad weather conditions');

        $this->assertNotNull($hike->fresh()->cancelled_at);
    });

    it('fails to cancel a draft hike', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->draft()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/cancel", [
                'cancellation_reason' => 'Changed plans',
            ]);

        $response->assertForbidden();
    });

    it('requires cancellation reason', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->published()->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cancellation_reason']);
    });
});

describe('delete group hike', function () {
    it('admin can delete any hike', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->published()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/group-hikes/{$hike->id}");

        $response->assertOk();
        $this->assertSoftDeleted($hike);
    });

    it('organizer can delete own draft hike', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        $hike = GroupHike::factory()->draft()->create([
            'organizer_id' => $organizer->id,
            'created_by' => $organizer->id,
        ]);

        $response = $this->actingAs($organizer)
            ->deleteJson("/api/admin/group-hikes/{$hike->id}");

        $response->assertOk();
        $this->assertSoftDeleted($hike);
    });

    it('organizer cannot delete own published hike', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        $hike = GroupHike::factory()->published()->create([
            'organizer_id' => $organizer->id,
            'created_by' => $organizer->id,
        ]);

        $response = $this->actingAs($organizer)
            ->deleteJson("/api/admin/group-hikes/{$hike->id}");

        $response->assertForbidden();
    });
});

describe('gallery reorder', function () {
    it('admin can reorder gallery images', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $hike = GroupHike::factory()->create();
        $media1 = Media::factory()->create();
        $media2 = Media::factory()->create();

        $image1 = $hike->images()->create(['media_id' => $media1->id, 'sort_order' => 0, 'created_at' => now()]);
        $image2 = $hike->images()->create(['media_id' => $media2->id, 'sort_order' => 1, 'created_at' => now()]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/group-hikes/{$hike->id}/gallery/reorder", [
                'image_ids' => [$image2->id, $image1->id],
            ]);

        $response->assertOk();

        $this->assertEquals(0, $image2->fresh()->sort_order);
        $this->assertEquals(1, $image1->fresh()->sort_order);
    });
});

describe('list group hikes', function () {
    it('admin sees all hikes', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        GroupHike::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/group-hikes');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 5);
    });

    it('organizer only sees own hikes', function () {
        $organizer = User::factory()->withRole($this->organizerRole)->create();
        $other = User::factory()->withRole($this->organizerRole)->create();

        GroupHike::factory()->count(3)->create(['organizer_id' => $organizer->id, 'created_by' => $organizer->id]);
        GroupHike::factory()->count(2)->create(['organizer_id' => $other->id, 'created_by' => $other->id]);

        $response = $this->actingAs($organizer)
            ->getJson('/api/admin/group-hikes');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 3);
    });

    it('unauthenticated users get 401', function () {
        $response = $this->getJson('/api/admin/group-hikes');
        $response->assertUnauthorized();
    });
});
