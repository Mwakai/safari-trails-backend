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

describe('list users', function () {
    it('allows users with users.view permission to get all users', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->withRole($this->adminRole)->count(5)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonCount(6, 'data.users')
            ->assertJsonStructure([
                'data' => [
                    'users',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    });

    it('paginates users with custom per_page', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->withRole($this->adminRole)->count(10)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data.users')
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonPath('data.meta.total', 11)
            ->assertJsonPath('data.meta.last_page', 3);
    });

    it('searches users by name', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->withRole($this->adminRole)->create(['first_name' => 'Zxkfmzqjb', 'last_name' => 'Doe']);
        User::factory()->withRole($this->adminRole)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users?search=Zxkfmzqjb');

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });

    it('searches users by email', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->withRole($this->adminRole)->create(['email' => 'john@example.com']);
        User::factory()->withRole($this->adminRole)->create(['email' => 'jane@example.com']);

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users?search=john@example');

        $response->assertOk()
            ->assertJsonCount(1, 'data.users');
    });

    it('filters users by role', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->withRole($this->adminRole)->count(3)->create();
        User::factory()->withRole($this->superAdminRole)->count(2)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson("/api/admin/users?role_id={$this->adminRole->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data.users');
    });

    it('filters users by status', function () {
        $superAdmin = User::factory()->withRole($this->superAdminRole)->create();
        User::factory()->withRole($this->adminRole)->count(3)->create();
        User::factory()->withRole($this->adminRole)->inactive()->count(2)->create();

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/users?status=inactive');

        $response->assertOk()
            ->assertJsonCount(2, 'data.users');
    });
});
