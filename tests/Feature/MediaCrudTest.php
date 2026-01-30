<?php

use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->superAdminRole = Role::factory()->superAdmin()->create();
    $this->adminRole = Role::factory()->admin()->create();
    $this->contentManagerRole = Role::factory()->contentManager()->create();
    $this->groupHikeOrganizerRole = Role::factory()->groupHikeOrganizer()->create();
});

describe('list media', function () {
    it('allows users with media.view permission to list media', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Media::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/media');

        $response->assertOk()
            ->assertJsonCount(3, 'data.media');
    });

    it('filters media by type', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Media::factory()->image()->count(2)->create();
        Media::factory()->document()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/media?type=image');

        $response->assertOk()
            ->assertJsonCount(2, 'data.media');
    });

    it('returns pagination metadata', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        Media::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/media');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['meta' => ['current_page', 'last_page', 'per_page', 'total']]]);
    });

    it('denies users without media.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/media');

        $response->assertForbidden();
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/admin/media');

        $response->assertUnauthorized();
    });
});

describe('upload media', function () {
    it('allows users with media.create permission to upload an image', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();
        $file = UploadedFile::fake()->image('chania-falls.jpg', 1200, 800);

        $response = $this->actingAs($contentManager)
            ->postJson('/api/admin/media', [
                'file' => $file,
                'alt_text' => 'Chania Falls view',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.media.type', 'image')
            ->assertJsonPath('data.media.alt_text', 'Chania Falls view');

        $this->assertDatabaseHas('media', [
            'original_filename' => 'chania-falls.jpg',
            'type' => 'image',
            'uploaded_by' => $contentManager->id,
        ]);
    });

    it('allows uploading a pdf document', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $file = UploadedFile::fake()->create('trail-guide.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/media', [
                'file' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.media.type', 'document');
    });

    it('denies users without media.create permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['media.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)
            ->postJson('/api/admin/media', [
                'file' => $file,
            ]);

        $response->assertForbidden();
    });

    it('validates file is required', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/media', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('validates file type', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/media', [
                'file' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('returns 401 for unauthenticated request', function () {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/admin/media', [
            'file' => $file,
        ]);

        $response->assertUnauthorized();
    });
});

describe('show media', function () {
    it('allows users with media.view permission to view media', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/media/{$media->id}");

        $response->assertOk()
            ->assertJsonPath('data.media.id', $media->id);
    });

    it('denies users without media.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/admin/media/{$media->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent media', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/media/999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $media = Media::factory()->create();

        $response = $this->getJson("/api/admin/media/{$media->id}");

        $response->assertUnauthorized();
    });
});

describe('update media', function () {
    it('allows users with media.update permission to update alt text', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();
        $media = Media::factory()->create(['alt_text' => 'Old alt text']);

        $response = $this->actingAs($contentManager)
            ->putJson("/api/admin/media/{$media->id}", [
                'alt_text' => 'New alt text',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.media.alt_text', 'New alt text');
    });

    it('denies users without media.update permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['media.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($user)
            ->putJson("/api/admin/media/{$media->id}", [
                'alt_text' => 'Updated',
            ]);

        $response->assertForbidden();
    });

    it('returns 401 for unauthenticated request', function () {
        $media = Media::factory()->create();

        $response = $this->putJson("/api/admin/media/{$media->id}", [
            'alt_text' => 'Updated',
        ]);

        $response->assertUnauthorized();
    });
});

describe('delete media', function () {
    it('allows users with media.delete permission to delete media', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Media deleted successfully');

        $this->assertSoftDeleted('media', ['id' => $media->id]);
    });

    it('denies users without media.delete permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['media.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $media = Media::factory()->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/admin/media/{$media->id}");

        $response->assertForbidden();
    });

    it('returns 404 for non-existent media', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/media/999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $media = Media::factory()->create();

        $response = $this->deleteJson("/api/admin/media/{$media->id}");

        $response->assertUnauthorized();
    });
});
