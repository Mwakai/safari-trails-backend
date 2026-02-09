<?php

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::factory()->admin()->create();
    $this->admin = User::factory()->withRole($this->adminRole)->create();
});

describe('pagination', function () {
    it('uses default per_page of 15', function () {
        ActivityLog::factory()->count(20)->causedBy($this->admin)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 15);
    });

    it('accepts custom per_page', function () {
        ActivityLog::factory()->count(10)->causedBy($this->admin)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?per_page=5');

        $response->assertOk()
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonCount(5, 'data.activity_logs');
    });

    it('rejects per_page over 100', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?per_page=101');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });
});

describe('sorting', function () {
    it('sorts by created_at descending by default', function () {
        ActivityLog::factory()->create(['created_at' => now()->subDays(2)]);
        ActivityLog::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs');

        $response->assertOk();
        $logs = $response->json('data.activity_logs');
        expect($logs[0]['created_at'])->toBeGreaterThan($logs[1]['created_at']);
    });

    it('sorts by log_name ascending', function () {
        ActivityLog::factory()->withLogName('users')->create();
        ActivityLog::factory()->withLogName('amenities')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?sort=log_name&order=asc');

        $response->assertOk();
        $logs = $response->json('data.activity_logs');
        expect($logs[0]['log_name'])->toBe('amenities');
        expect($logs[1]['log_name'])->toBe('users');
    });

    it('sorts by event descending', function () {
        ActivityLog::factory()->withEvent('created')->create();
        ActivityLog::factory()->withEvent('updated')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?sort=event&order=desc');

        $response->assertOk();
        $logs = $response->json('data.activity_logs');
        expect($logs[0]['event'])->toBe('updated');
        expect($logs[1]['event'])->toBe('created');
    });

    it('rejects invalid sort column', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?sort=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    });
});

describe('search', function () {
    it('searches by log_name', function () {
        ActivityLog::factory()->withLogName('users')->create();
        ActivityLog::factory()->withLogName('trails')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?search=users');

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });

    it('searches by event', function () {
        ActivityLog::factory()->withEvent('created')->create();
        ActivityLog::factory()->withEvent('deleted')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?search=created');

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });
});

describe('log_name filter', function () {
    it('filters by single log_name', function () {
        ActivityLog::factory()->withLogName('users')->create();
        ActivityLog::factory()->withLogName('trails')->create();
        ActivityLog::factory()->withLogName('media')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?log_name=users');

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });

    it('filters by comma-separated log_names', function () {
        ActivityLog::factory()->withLogName('users')->create();
        ActivityLog::factory()->withLogName('trails')->create();
        ActivityLog::factory()->withLogName('media')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?log_name=users,trails');

        $response->assertOk()
            ->assertJsonCount(2, 'data.activity_logs');
    });
});

describe('event filter', function () {
    it('filters by single event', function () {
        ActivityLog::factory()->withEvent('created')->create();
        ActivityLog::factory()->withEvent('updated')->create();
        ActivityLog::factory()->withEvent('deleted')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?event=created');

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });

    it('filters by comma-separated events', function () {
        ActivityLog::factory()->withEvent('created')->create();
        ActivityLog::factory()->withEvent('updated')->create();
        ActivityLog::factory()->withEvent('deleted')->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?event=created,deleted');

        $response->assertOk()
            ->assertJsonCount(2, 'data.activity_logs');
    });
});

describe('causer filter', function () {
    it('filters by causer_id', function () {
        $otherUser = User::factory()->create();
        ActivityLog::factory()->causedBy($this->admin)->create();
        ActivityLog::factory()->causedBy($otherUser)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/activity-logs?causer_id={$this->admin->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });
});

describe('subject filter', function () {
    it('filters by subject_type', function () {
        ActivityLog::factory()->forSubject($this->admin)->create();
        ActivityLog::factory()->create(['subject_type' => 'App\\Models\\Trail', 'subject_id' => 1]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?subject_type=App%5CModels%5CUser');

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });

    it('filters by subject_id', function () {
        ActivityLog::factory()->create(['subject_type' => 'App\\Models\\User', 'subject_id' => 1]);
        ActivityLog::factory()->create(['subject_type' => 'App\\Models\\User', 'subject_id' => 2]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs?subject_id=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });
});

describe('date range filter', function () {
    it('filters by created_after and created_before', function () {
        ActivityLog::factory()->create(['created_at' => now()->subDays(10)]);
        ActivityLog::factory()->create(['created_at' => now()->subDays(3)]);
        ActivityLog::factory()->create(['created_at' => now()]);

        $after = now()->subDays(5)->format('Y-m-d');
        $before = now()->subDay()->format('Y-m-d');

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/activity-logs?created_after={$after}&created_before={$before}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.activity_logs');
    });
});

describe('authorization', function () {
    it('denies users without activity_logs.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/activity-logs');

        $response->assertForbidden();
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/admin/activity-logs');

        $response->assertUnauthorized();
    });
});

describe('show', function () {
    it('returns a single activity log', function () {
        $log = ActivityLog::factory()
            ->withLogName('users')
            ->withEvent('created')
            ->causedBy($this->admin)
            ->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/activity-logs/{$log->id}");

        $response->assertOk()
            ->assertJsonPath('data.activity_log.id', $log->id)
            ->assertJsonPath('data.activity_log.log_name', 'users')
            ->assertJsonPath('data.activity_log.event', 'created');
    });

    it('returns 404 for non-existent activity log', function () {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/activity-logs/99999');

        $response->assertNotFound();
    });

    it('denies users without activity_logs.view permission', function () {
        $noPermissionRole = Role::factory()->create(['permissions' => []]);
        $user = User::factory()->withRole($noPermissionRole)->create();
        $log = ActivityLog::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/admin/activity-logs/{$log->id}");

        $response->assertForbidden();
    });

    it('eager loads causer', function () {
        $log = ActivityLog::factory()
            ->causedBy($this->admin)
            ->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/activity-logs/{$log->id}");

        $response->assertOk()
            ->assertJsonPath('data.activity_log.causer.id', $this->admin->id);
    });
});
