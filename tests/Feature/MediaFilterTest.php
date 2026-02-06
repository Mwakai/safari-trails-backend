<?php

use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
});

describe('pagination', function () {
    it('uses default per_page of 20', function () {
        Media::factory()->count(25)->create(['uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 20);
    });

    it('accepts custom per_page', function () {
        Media::factory()->count(10)->create(['uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?per_page=5');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonCount(5, 'data.media');
    });

    it('rejects per_page over 100', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });
});

describe('sorting', function () {
    it('sorts by original_filename ascending', function () {
        Media::factory()->create(['original_filename' => 'zebra.jpg', 'uploaded_by' => $this->admin->id]);
        Media::factory()->create(['original_filename' => 'alpha.jpg', 'uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?sort=original_filename&order=asc');

        $response->assertOk();
        $media = $response->json('data.media');
        expect($media[0]['original_filename'])->toBe('alpha.jpg');
    });

    it('sorts by size descending', function () {
        Media::factory()->create(['size' => 100000, 'uploaded_by' => $this->admin->id]);
        Media::factory()->create(['size' => 5000000, 'uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?sort=size&order=desc');

        $response->assertOk();
        $media = $response->json('data.media');
        expect($media[0]['size'])->toBeGreaterThan($media[1]['size']);
    });

    it('rejects invalid sort column', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?sort=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    });
});

describe('search', function () {
    it('searches by original_filename', function () {
        Media::factory()->create(['original_filename' => 'mountain-view.jpg', 'uploaded_by' => $this->admin->id]);
        Media::factory()->create(['original_filename' => 'sunset.jpg', 'uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?search=mountain');

        $response->assertOk()
            ->assertJsonCount(1, 'data.media');
    });

    it('searches by alt_text', function () {
        Media::factory()->create(['alt_text' => 'Beautiful waterfall scenery', 'uploaded_by' => $this->admin->id]);
        Media::factory()->create(['alt_text' => 'Desert landscape', 'uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?search=waterfall');

        $response->assertOk()
            ->assertJsonCount(1, 'data.media');
    });
});

describe('type filter', function () {
    it('filters by single type', function () {
        Media::factory()->image()->create(['uploaded_by' => $this->admin->id]);
        Media::factory()->video()->create(['uploaded_by' => $this->admin->id]);
        Media::factory()->document()->create(['uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?type=image');

        $response->assertOk()
            ->assertJsonCount(1, 'data.media');
    });

    it('filters by comma-separated types', function () {
        Media::factory()->image()->create(['uploaded_by' => $this->admin->id]);
        Media::factory()->video()->create(['uploaded_by' => $this->admin->id]);
        Media::factory()->document()->create(['uploaded_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/media?type=image,video');

        $response->assertOk()
            ->assertJsonCount(2, 'data.media');
    });
});

describe('uploaded_by filter', function () {
    it('filters by uploaded_by', function () {
        $otherUser = User::factory()->create();
        Media::factory()->create(['uploaded_by' => $this->admin->id]);
        Media::factory()->create(['uploaded_by' => $otherUser->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/media?uploaded_by={$this->admin->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.media');
    });
});

describe('date range filter', function () {
    it('filters by created_after and created_before', function () {
        Media::factory()->create(['uploaded_by' => $this->admin->id, 'created_at' => now()->subDays(10)]);
        Media::factory()->create(['uploaded_by' => $this->admin->id, 'created_at' => now()->subDays(3)]);
        Media::factory()->create(['uploaded_by' => $this->admin->id, 'created_at' => now()]);

        $after = now()->subDays(5)->format('Y-m-d');
        $before = now()->subDay()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/media?created_after={$after}&created_before={$before}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.media');
    });
});

describe('trashed filter', function () {
    it('includes trashed media with "with" when user has permission', function () {
        $superAdminRole = Role::factory()->superAdmin()->create();
        $superAdmin = User::factory()->withRole($superAdminRole)->create();

        Media::factory()->create(['uploaded_by' => $superAdmin->id]);
        $trashed = Media::factory()->create(['uploaded_by' => $superAdmin->id]);
        $trashed->delete();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/media?trashed=with');

        $response->assertOk()
            ->assertJsonPath('data.meta.total', 2);
    });
});
