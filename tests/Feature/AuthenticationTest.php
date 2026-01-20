<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::factory()->superAdmin()->create();
    $this->adminRole = Role::factory()->admin()->create();
});

describe('login', function () {
    it('logs in a user with valid credentials', function () {
        $user = User::factory()->withRole($this->superAdminRole)->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                ],
                'message',
            ]);
    });

    it('fails to login with invalid credentials', function () {
        User::factory()->withRole($this->superAdminRole)->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized();
    });

    it('fails to login when user is inactive', function () {
        User::factory()->withRole($this->superAdminRole)->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertForbidden()
            ->assertJson(['message' => 'Your account is inactive']);
    });

    it('updates last_login_at on successful login', function () {
        $user = User::factory()->withRole($this->superAdminRole)->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'last_login_at' => null,
        ]);

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $user->refresh();
        expect($user->last_login_at)->not->toBeNull();
    });
});

describe('register', function () {
    it('allows a super admin to register a new user', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/register', [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->adminRole->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'access_token',
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'created_by' => $superAdmin->id,
        ]);
    });

    it('fails to register without authentication', function () {
        $response = $this->postJson('/api/admin/register', [
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->adminRole->id,
        ]);

        $response->assertUnauthorized();
    });

    it('validates required fields when registering', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($superAdmin)
            ->postJson('/api/admin/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'password',
                'role_id',
            ]);
    });
});

describe('logout', function () {
    it('logs out a user', function () {
        $user = User::factory()->withRole($this->superAdminRole)->create();

        $response = $this->actingAs($user)
            ->postJson('/api/admin/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logged out successfully');
    });
});

describe('me', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->withRole($this->superAdminRole)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/admin/me');

        $response->assertOk()
            ->assertJsonPath('data.user.first_name', 'John')
            ->assertJsonPath('data.user.last_name', 'Doe');
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/admin/me');

        $response->assertUnauthorized();
    });
});

describe('getAllUsers', function () {
    it('allows users with users.view permission to get all users', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->withRole($this->adminRole)->count(5)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonCount(6, 'users');
    });
});
