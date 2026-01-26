<?php

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

describe('permission middleware', function () {
    it('allows super admin to access any protected route', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users');

        $response->assertOk();
    });

    it('allows admin with users.view permission to access users endpoint', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users');

        $response->assertOk();
    });

    it('denies content manager access to users endpoint', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($contentManager)
            ->getJson('/api/admin/users');

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to perform this action.']);
    });

    it('denies group hike organizer access to users endpoint', function () {
        $organizer = User::factory()->withRole($this->groupHikeOrganizerRole)->create();

        $response = $this->actingAs($organizer)
            ->getJson('/api/admin/users');

        $response->assertForbidden();
    });

    it('denies inactive users access to protected routes', function () {
        $inactiveAdmin = User::factory()
            ->withRole($this->adminRole)
            ->inactive()
            ->create();

        $response = $this->actingAs($inactiveAdmin)
            ->getJson('/api/admin/users');

        $response->assertForbidden()
            ->assertJson(['message' => 'Your account is inactive.']);
    });

    it('allows admin to create new users', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->contentManagerRole->id,
            ]);

        $response->assertCreated();
    });

    it('denies content manager from creating new users', function () {
        $contentManager = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($contentManager)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->groupHikeOrganizerRole->id,
            ]);

        $response->assertForbidden();
    });
});
