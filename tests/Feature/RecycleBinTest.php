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
