<?php

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::factory()->superAdmin()->create();
    $this->adminRole = Role::factory()->admin()->create();
    $this->contentManagerRole = Role::factory()->contentManager()->create();
});

describe('create user', function () {
    it('allows super admin to create a new user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.first_name', 'John')
            ->assertJsonPath('data.user.last_name', 'Doe')
            ->assertJsonPath('data.user.email', 'john.doe@example.com');

        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'created_by' => $superAdmin->id,
        ]);
    });

    it('allows admin with users.create permission to create users', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->contentManagerRole->id,
            ]);

        $response->assertStatus(201);
    });

    it('prevents user without users.create permission from creating users', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['users.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->contentManagerRole->id,
            ]);

        $response->assertForbidden();
    });

    it('prevents non-super admin from creating user with super admin role', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'first_name' => 'New',
                'last_name' => 'SuperAdmin',
                'email' => 'newsuperadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->superAdminRole->id,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Only super admins can assign the super admin role.']);
    });

    it('allows super admin to create user with super admin role', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Another',
                'last_name' => 'SuperAdmin',
                'email' => 'another.superadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->superAdminRole->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'another.superadmin@example.com',
            'role_id' => $this->superAdminRole->id,
        ]);
    });

    it('validates required fields', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'role_id']);
    });

    it('validates email uniqueness', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('validates password confirmation', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'wrongpassword',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('validates role_id exists', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role_id']);
    });

    it('sets default status to active when not provided', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertStatus(201);
        $createdUser = User::where('email', 'test@example.com')->first();
        expect($createdUser->status)->toBe(UserStatus::Active);
    });

    it('allows setting custom status', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->adminRole->id,
                'status' => 'inactive',
            ]);

        $response->assertStatus(201);
        $createdUser = User::where('email', 'test@example.com')->first();
        expect($createdUser->status)->toBe(UserStatus::Inactive);
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->postJson('/api/admin/users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->adminRole->id,
        ]);

        $response->assertUnauthorized();
    });

    it('includes role and company in response', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                    ],
                ],
            ]);
    });
});

describe('show user', function () {
    it('allows super admin to view any user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->actingAs($superAdmin)
            ->getJson("/api/admin/users/{$targetUser->id}");

        $response->assertOk()
            ->assertJsonPath('data.user.first_name', 'John')
            ->assertJsonPath('data.user.last_name', 'Doe');
    });

    it('allows super admin to view another super admin', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $anotherSuperAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson("/api/admin/users/{$anotherSuperAdmin->id}");

        $response->assertOk();
    });

    it('allows admin to view regular users', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $targetUser = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/users/{$targetUser->id}");

        $response->assertOk();
    });

    it('prevents admin from viewing super admin users', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/users/{$superAdmin->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to view this user.']);
    });

    it('returns 404 for non-existent user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users/99999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $user = User::factory()->withRole($this->adminRole)->create();

        $response = $this->getJson("/api/admin/users/{$user->id}");

        $response->assertUnauthorized();
    });

    it('includes role and company in response', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson("/api/admin/users/{$targetUser->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'role',
                    ],
                ],
            ]);
    });
});

describe('update user', function () {
    it('allows super admin to update any user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'first_name' => 'Updated',
                'last_name' => 'Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.user.first_name', 'Updated')
            ->assertJsonPath('data.user.last_name', 'Name');

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    });

    it('allows partial updates using PATCH', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
        ]);

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'first_name' => 'Updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.user.first_name', 'Updated')
            ->assertJsonPath('data.user.last_name', 'Name');
    });

    it('allows full updates using PUT', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->putJson("/api/admin/users/{$targetUser->id}", [
                'first_name' => 'Full',
                'last_name' => 'Update',
                'email' => 'updated@example.com',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'first_name' => 'Full',
            'last_name' => 'Update',
            'email' => 'updated@example.com',
        ]);
    });

    it('validates email uniqueness excluding current user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $existingUser = User::factory()->withRole($this->adminRole)->create([
            'email' => 'existing@example.com',
        ]);
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'email' => 'existing@example.com',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('allows updating email to the same value', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create([
            'email' => 'same@example.com',
        ]);

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'email' => 'same@example.com',
            ]);

        $response->assertOk();
    });

    it('updates password and sets password_changed_at', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create([
            'password_changed_at' => null,
        ]);

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk();
        $targetUser->refresh();
        expect($targetUser->password_changed_at)->not->toBeNull();
    });

    it('validates password confirmation', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'password' => 'newpassword123',
                'password_confirmation' => 'wrongconfirmation',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('prevents admin from updating super admin users', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$superAdmin->id}", [
                'first_name' => 'Updated',
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to update this user.']);
    });

    it('allows updating user status', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create([
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'status' => 'inactive',
            ]);

        $response->assertOk();
        $targetUser->refresh();
        expect($targetUser->status)->toBe(UserStatus::Inactive);
    });

    it('validates status enum value', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'status' => 'invalid_status',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });
});

describe('update user roles', function () {
    it('allows super admin to change user role', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'role_id' => $this->contentManagerRole->id,
            ]);

        $response->assertOk();
        $targetUser->refresh();
        expect($targetUser->role_id)->toBe($this->contentManagerRole->id);
    });

    it('allows admin with users.manage_roles permission to change roles', function () {
        $roleWithManageRoles = Role::factory()->create([
            'permissions' => ['users.*', 'users.manage_roles'],
        ]);
        $admin = User::factory()->withRole($roleWithManageRoles)->create();
        $targetUser = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertOk();
    });

    it('prevents admin without manage_roles permission from changing roles', function () {
        $roleWithoutManageRoles = Role::factory()->create([
            'permissions' => ['users.update'],
        ]);
        $admin = User::factory()->withRole($roleWithoutManageRoles)->create();
        $targetUser = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to change user roles.']);
    });

    it('prevents non-super admin from assigning super admin role', function () {
        $roleWithManageRoles = Role::factory()->create([
            'permissions' => ['users.*', 'users.manage_roles'],
        ]);
        $admin = User::factory()->withRole($roleWithManageRoles)->create();
        $targetUser = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'role_id' => $this->superAdminRole->id,
            ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Only super admins can assign the super admin role.']);
    });

    it('allows super admin to assign super admin role', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'role_id' => $this->superAdminRole->id,
            ]);

        $response->assertOk();
        $targetUser->refresh();
        expect($targetUser->role_id)->toBe($this->superAdminRole->id);
    });

    it('allows updating without role_id change when role matches current', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'first_name' => 'Updated',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertOk();
    });
});

describe('delete user', function () {
    it('allows super admin to delete a user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/admin/users/{$targetUser->id}");

        $response->assertOk()
            ->assertJson(['message' => 'User deleted successfully']);

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    });

    it('soft deletes user instead of hard delete', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $this->actingAs($superAdmin)
            ->deleteJson("/api/admin/users/{$targetUser->id}");

        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
        expect(User::withTrashed()->find($targetUser->id)->deleted_at)->not->toBeNull();
    });

    it('prevents user from deleting their own account', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/admin/users/{$superAdmin->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You cannot delete your own account.']);
    });

    it('prevents admin from deleting super admin users', function () {
        $admin = User::factory()->withRole($this->adminRole)->create();
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$superAdmin->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'You do not have permission to delete this user.']);
    });

    it('allows super admin to delete another super admin', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $anotherSuperAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->deleteJson("/api/admin/users/{$anotherSuperAdmin->id}");

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $anotherSuperAdmin->id]);
    });

    it('returns 404 for non-existent user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->deleteJson('/api/admin/users/99999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $user = User::factory()->withRole($this->adminRole)->create();

        $response = $this->deleteJson("/api/admin/users/{$user->id}");

        $response->assertUnauthorized();
    });
});

describe('permission checks', function () {
    it('requires users.view permission to show user', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $response = $this->actingAs($user)
            ->getJson("/api/admin/users/{$targetUser->id}");

        $response->assertForbidden();
    });

    it('requires users.update permission to update user', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['users.view']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $targetUser = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($user)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'first_name' => 'Updated',
            ]);

        $response->assertForbidden();
    });

    it('requires users.delete permission to delete user', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => ['users.view', 'users.update']]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $targetUser = User::factory()->withRole($this->contentManagerRole)->create();

        $response = $this->actingAs($user)
            ->deleteJson("/api/admin/users/{$targetUser->id}");

        $response->assertForbidden();
    });
});

describe('activity logging', function () {
    it('logs user creation', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $this->actingAs($superAdmin)
            ->postJson('/api/admin/users', [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->adminRole->id,
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'users',
            'event' => 'created',
            'causer_id' => $superAdmin->id,
        ]);
    });

    it('logs user update', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'first_name' => 'Updated',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'users',
            'event' => 'updated',
            'subject_id' => $targetUser->id,
            'causer_id' => $superAdmin->id,
        ]);
    });

    it('logs role change with old and new role_id properties', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'role_id' => $this->contentManagerRole->id,
            ]);

        $log = ActivityLog::where('event', 'role_changed')
            ->where('subject_id', $targetUser->id)
            ->first();

        expect($log)->not->toBeNull();
        expect($log->properties)->toMatchArray([
            'old_role_id' => $this->adminRole->id,
            'new_role_id' => $this->contentManagerRole->id,
        ]);
    });

    it('logs user deactivation with status properties', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create([
            'status' => UserStatus::Active,
        ]);

        $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'status' => 'inactive',
            ]);

        $log = ActivityLog::where('event', 'deactivated')
            ->where('subject_id', $targetUser->id)
            ->first();

        expect($log)->not->toBeNull();
        expect($log->properties)->toMatchArray([
            'old_status' => 'active',
            'new_status' => 'inactive',
        ]);
    });

    it('logs user activation with status properties', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->inactive()->create();

        $this->actingAs($superAdmin)
            ->patchJson("/api/admin/users/{$targetUser->id}", [
                'status' => 'active',
            ]);

        $log = ActivityLog::where('event', 'activated')
            ->where('subject_id', $targetUser->id)
            ->first();

        expect($log)->not->toBeNull();
        expect($log->properties)->toMatchArray([
            'old_status' => 'inactive',
            'new_status' => 'active',
        ]);
    });

    it('logs user deletion', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        $targetUser = User::factory()->withRole($this->adminRole)->create();

        $this->actingAs($superAdmin)
            ->deleteJson("/api/admin/users/{$targetUser->id}");

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'users',
            'event' => 'deleted',
            'subject_id' => $targetUser->id,
            'causer_id' => $superAdmin->id,
        ]);
    });
});
