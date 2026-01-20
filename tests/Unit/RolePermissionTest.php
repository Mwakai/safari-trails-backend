<?php

use App\Models\Role;

describe('role permission matching', function () {
    it('matches exact permissions', function () {
        $role = new Role([
            'name' => 'Test Role',
            'slug' => 'test_role',
            'permissions' => ['users.view', 'users.create'],
        ]);

        expect($role->hasPermission('users.view'))->toBeTrue();
        expect($role->hasPermission('users.create'))->toBeTrue();
        expect($role->hasPermission('users.delete'))->toBeFalse();
    });

    it('matches wildcard all permission', function () {
        $role = new Role([
            'name' => 'Super Admin',
            'slug' => 'super_admin',
            'permissions' => ['*'],
        ]);

        expect($role->hasPermission('users.view'))->toBeTrue();
        expect($role->hasPermission('users.create'))->toBeTrue();
        expect($role->hasPermission('anything.here'))->toBeTrue();
    });

    it('matches wildcard group permission', function () {
        $role = new Role([
            'name' => 'Admin',
            'slug' => 'admin',
            'permissions' => ['users.*', 'media.view'],
        ]);

        expect($role->hasPermission('users.view'))->toBeTrue();
        expect($role->hasPermission('users.create'))->toBeTrue();
        expect($role->hasPermission('users.delete'))->toBeTrue();
        expect($role->hasPermission('media.view'))->toBeTrue();
        expect($role->hasPermission('media.delete'))->toBeFalse();
    });

    it('returns false for empty permissions', function () {
        $role = new Role([
            'name' => 'No Permissions',
            'slug' => 'no_permissions',
            'permissions' => [],
        ]);

        expect($role->hasPermission('users.view'))->toBeFalse();
    });

    it('checks hasAnyPermission correctly', function () {
        $role = new Role([
            'name' => 'Test Role',
            'slug' => 'test_role',
            'permissions' => ['users.view'],
        ]);

        expect($role->hasAnyPermission(['users.view', 'users.create']))->toBeTrue();
        expect($role->hasAnyPermission(['users.delete', 'media.view']))->toBeFalse();
    });

    it('returns false for null permissions', function () {
        $role = new Role([
            'name' => 'Null Permissions',
            'slug' => 'null_permissions',
            'permissions' => null,
        ]);

        expect($role->hasPermission('users.view'))->toBeFalse();
    });
});
