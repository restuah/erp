<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('superadmin can view users list', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $response = $this->actingAs($superadmin)->get(route('users.index'));

    $response->assertOk();
});

test('user can be created with avatar and role', function () {
    Storage::fake('public');

    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $editorRole = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

    $avatar = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->actingAs($superadmin)->post(route('users.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'avatar' => $avatar,
        'roles' => ['Editor'],
    ]);

    $response->assertRedirect(route('users.index'));

    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->id)->toBeString();
    expect(strlen($user->id))->toBe(36); // UUID string length
    expect($user->hasRole('Editor'))->toBeTrue();
    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);
});

test('user can be soft deleted', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $user = User::factory()->create();

    $response = $this->actingAs($superadmin)->delete(route('users.destroy', $user->id));

    $response->assertRedirect(route('users.index'));
    $this->assertSoftDeleted($user);
});

test('user cannot delete their own account from user management', function () {
    $superadmin = User::factory()->create();
    $role = Role::create(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $response = $this->actingAs($superadmin)->delete(route('users.destroy', $superadmin->id));

    $response->assertSessionHas('error');
    $this->assertNotSoftDeleted($superadmin);
});
