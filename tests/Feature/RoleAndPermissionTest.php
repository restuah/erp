<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('role and permission can be created and soft deleted', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    // Create Permission
    $resPerm = $this->actingAs($superadmin)->post(route('permissions.store'), [
        'name' => 'reports.view',
        'guard_name' => 'web',
    ]);
    $resPerm->assertRedirect(route('permissions.index'));

    $permission = Permission::where('name', 'reports.view')->first();
    expect($permission)->not->toBeNull();
    expect(strlen($permission->id))->toBe(36); // UUID

    // Create Role with permission
    $resRole = $this->actingAs($superadmin)->post(route('roles.store'), [
        'name' => 'Auditor',
        'guard_name' => 'web',
        'permissions' => ['reports.view'],
    ]);
    $resRole->assertRedirect(route('roles.index'));

    $auditor = Role::where('name', 'Auditor')->first();
    expect($auditor)->not->toBeNull();
    expect(strlen($auditor->id))->toBe(36); // UUID
    expect($auditor->hasPermissionTo('reports.view'))->toBeTrue();

    // Soft delete role
    $resDel = $this->actingAs($superadmin)->delete(route('roles.destroy', $auditor->id));
    $resDel->assertRedirect(route('roles.index'));
    $this->assertSoftDeleted($auditor);

    // Soft delete permission
    $resDelPerm = $this->actingAs($superadmin)->delete(route('permissions.destroy', $permission->id));
    $resDelPerm->assertRedirect(route('permissions.index'));
    $this->assertSoftDeleted($permission);
});

test('superadmin role cannot be deleted', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $resDel = $this->actingAs($superadmin)->delete(route('roles.destroy', $role->id));
    $resDel->assertSessionHas('error');
    $this->assertNotSoftDeleted($role);
});
