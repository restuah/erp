<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('superadmin can access recycle bin index', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $response = $this->actingAs($superadmin)->get(route('recycle-bin.index'));

    $response->assertOk();
});

test('soft-deleted user can be restored from recycle bin', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $user = User::factory()->create();
    $user->delete();
    expect(User::count())->toBe(1);
    expect(User::onlyTrashed()->count())->toBe(1);

    $response = $this->actingAs($superadmin)->post(
        route('recycle-bin.restore', ['type' => 'users', 'id' => $user->id])
    );

    $response->assertRedirect();
    expect(User::count())->toBe(2);
    expect(User::onlyTrashed()->count())->toBe(0);
});

test('soft-deleted user can be permanently deleted and removes avatar file', function () {
    Storage::fake('public');

    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $avatar = UploadedFile::fake()->image('avatar.jpg');
    $path = $avatar->store('avatars', 'public');

    $user = User::factory()->create(['avatar' => $path]);
    $user->delete();

    Storage::disk('public')->assertExists($path);

    $response = $this->actingAs($superadmin)->delete(
        route('recycle-bin.force-delete', ['type' => 'users', 'id' => $user->id])
    );

    $response->assertRedirect();
    expect(User::withTrashed()->find($user->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('soft-deleted role and permission can be restored and permanently deleted', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    // Create and soft-delete a role
    $testRole = Role::create(['name' => 'TesterRole', 'guard_name' => 'web']);
    $testRole->delete();

    // Create and soft-delete a permission
    $testPerm = Permission::create(['name' => 'tester.perm', 'guard_name' => 'web']);
    $testPerm->delete();

    expect(Role::onlyTrashed()->count())->toBe(1);
    expect(Permission::onlyTrashed()->count())->toBe(1);

    // Restore role
    $this->actingAs($superadmin)->post(
        route('recycle-bin.restore', ['type' => 'roles', 'id' => $testRole->id])
    )->assertRedirect();
    expect(Role::onlyTrashed()->count())->toBe(0);

    // Force delete permission
    $this->actingAs($superadmin)->delete(
        route('recycle-bin.force-delete', ['type' => 'permissions', 'id' => $testPerm->id])
    )->assertRedirect();
    expect(Permission::withTrashed()->find($testPerm->id))->toBeNull();
});

test('superadmin can restore all items in a category', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    User::factory()->count(3)->create()->each->delete();
    expect(User::onlyTrashed()->count())->toBe(3);

    $this->actingAs($superadmin)->post(
        route('recycle-bin.restore-all', ['type' => 'users'])
    )->assertRedirect();

    expect(User::onlyTrashed()->count())->toBe(0);
    expect(User::count())->toBe(4); // 3 + superadmin
});

test('superadmin can empty trash in a category', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    User::factory()->count(3)->create()->each->delete();
    expect(User::onlyTrashed()->count())->toBe(3);

    $this->actingAs($superadmin)->delete(
        route('recycle-bin.empty', ['type' => 'users'])
    )->assertRedirect();

    expect(User::onlyTrashed()->count())->toBe(0);
    expect(User::withTrashed()->count())->toBe(1); // only superadmin remains
});

test('superadmin can restore all and empty customers category in recycle bin', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $customer1 = \App\Models\Customer::create(['customer_code' => 'CUST-0001', 'name' => 'Customer 1']);
    $customer2 = \App\Models\Customer::create(['customer_code' => 'CUST-0002', 'name' => 'Customer 2']);
    $customer1->delete();
    $customer2->delete();

    expect(\App\Models\Customer::onlyTrashed()->count())->toBe(2);

    $this->actingAs($superadmin)->post(
        route('recycle-bin.restore-all', ['type' => 'customers'])
    )->assertRedirect();

    expect(\App\Models\Customer::onlyTrashed()->count())->toBe(0);
    expect(\App\Models\Customer::count())->toBe(2);

    $customer1->delete();
    $customer2->delete();

    $this->actingAs($superadmin)->delete(
        route('recycle-bin.empty', ['type' => 'customers'])
    )->assertRedirect();

    expect(\App\Models\Customer::withTrashed()->count())->toBe(0);
});

test('user with module restore permission can restore but cannot force delete', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $deptManager = User::factory()->create();
    $deptManager->givePermissionTo('departments.restore');

    $dept = \App\Models\Department::create([
        'code' => 'TEST_DEPT',
        'name' => 'Test Department',
        'is_active' => true,
    ]);
    $dept->delete();

    expect(\App\Models\Department::onlyTrashed()->count())->toBe(1);

    // Can access recycle bin and see departments
    $indexRes = $this->actingAs($deptManager)->get(route('recycle-bin.index', ['type' => 'departments']));
    $indexRes->assertOk();

    // Can restore
    $restoreRes = $this->actingAs($deptManager)->post(
        route('recycle-bin.restore', ['type' => 'departments', 'id' => $dept->id])
    );
    $restoreRes->assertRedirect();
    expect(\App\Models\Department::onlyTrashed()->count())->toBe(0);

    // Soft delete again
    $dept->delete();

    // Cannot force delete without departments.force_delete
    $forceDelRes = $this->actingAs($deptManager)->delete(
        route('recycle-bin.force-delete', ['type' => 'departments', 'id' => $dept->id])
    );
    $forceDelRes->assertForbidden();

    // Cannot empty trash without departments.force_delete
    $emptyRes = $this->actingAs($deptManager)->delete(
        route('recycle-bin.empty', ['type' => 'departments'])
    );
    $emptyRes->assertForbidden();
});

test('user with module force_delete permission can permanently delete but cannot restore', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $custManager = User::factory()->create();
    $custManager->givePermissionTo('customers.force_delete');

    $cust = \App\Models\Customer::create([
        'customer_code' => 'CUST-PERM',
        'name' => 'Permanent Test Customer',
    ]);
    $cust->delete();

    // Cannot restore without customers.restore
    $restoreRes = $this->actingAs($custManager)->post(
        route('recycle-bin.restore', ['type' => 'customers', 'id' => $cust->id])
    );
    $restoreRes->assertForbidden();

    // Can force delete
    $forceDelRes = $this->actingAs($custManager)->delete(
        route('recycle-bin.force-delete', ['type' => 'customers', 'id' => $cust->id])
    );
    $forceDelRes->assertRedirect();
    expect(\App\Models\Customer::withTrashed()->find($cust->id))->toBeNull();
});

