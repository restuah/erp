<?php

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;

test('superadmin can view activity logs index page', function () {
    $superadmin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    $response = $this->actingAs($superadmin)->get(route('activity-logs.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('ActivityLogs/Index'));
});

test('user creation automatically generates an activity log', function () {
    $admin = User::factory()->create(['name' => 'Admin Test']);
    $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
    $admin->assignRole($role);

    $this->actingAs($admin);

    $newUser = User::create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $log = ActivityLog::where('subject_id', $newUser->id)
        ->where('event', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($admin->id);
    expect($log->causer_name)->toBe('Admin Test');
    expect($log->log_name)->toBe('user');
    expect($log->properties['attributes']['name'])->toBe('Budi Santoso');
    expect($log->properties['attributes'])->not->toHaveKey('password'); // Password excluded
});

test('user update records old and new values diff', function () {
    $admin = User::factory()->create(['name' => 'Admin Actor']);
    $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
    $admin->assignRole($role);

    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($admin);

    $user->update([
        'name' => 'Nama Baru',
    ]);

    $log = ActivityLog::where('subject_id', $user->id)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($admin->id);
    expect($log->properties)->toHaveKey('old');
    expect($log->properties)->toHaveKey('attributes');
    expect($log->properties['old']['name'])->toBe('Nama Lama');
    expect($log->properties['attributes']['name'])->toBe('Nama Baru');
});

test('soft delete and restore generate respective activity logs', function () {
    $admin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
    $admin->assignRole($role);

    $user = User::factory()->create(['name' => 'Target User']);

    $this->actingAs($admin);

    // Soft delete
    $user->delete();

    $deleteLog = ActivityLog::where('subject_id', $user->id)
        ->where('event', 'deleted')
        ->first();

    expect($deleteLog)->not->toBeNull();
    expect($deleteLog->status)->toBe('warning');

    // Restore
    $user->restore();

    $restoreLog = ActivityLog::where('subject_id', $user->id)
        ->where('event', 'restored')
        ->first();

    expect($restoreLog)->not->toBeNull();
    expect($restoreLog->status)->toBe('success');
});

test('login and logout events automatically create activity logs', function () {
    $user = User::factory()->create(['name' => 'Login User']);

    // Trigger Login Event
    event(new Login('web', $user, false));

    $loginLog = ActivityLog::where('event', 'login')
        ->where('causer_id', $user->id)
        ->first();

    expect($loginLog)->not->toBeNull();
    expect($loginLog->status)->toBe('success');
    expect($loginLog->log_name)->toBe('auth');

    // Trigger Logout Event
    event(new Logout('web', $user));

    $logoutLog = ActivityLog::where('event', 'logout')
        ->where('causer_id', $user->id)
        ->first();

    expect($logoutLog)->not->toBeNull();
    expect($logoutLog->status)->toBe('info');
});

test('failed login attempt creates activity log with danger status', function () {
    event(new Failed('web', null, ['email' => 'hacker@example.com', 'password' => 'wrongpass']));

    $log = ActivityLog::where('event', 'failed_login')->first();

    expect($log)->not->toBeNull();
    expect($log->status)->toBe('danger');
    expect($log->properties['attempted_email'])->toBe('hacker@example.com');
});

test('activity logs can be exported as CSV', function () {
    $superadmin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    ActivityLog::create([
        'log_name' => 'user',
        'description' => 'Test log for export',
        'event' => 'created',
        'causer_name' => 'Tester',
        'status' => 'success',
    ]);

    $response = $this->actingAs($superadmin)->get(route('activity-logs.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('superadmin can purge old activity logs', function () {
    $superadmin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
    $superadmin->assignRole($role);

    // Old log
    $oldLog = ActivityLog::create([
        'log_name' => 'system',
        'description' => 'Old record',
        'event' => 'custom',
        'created_at' => now()->subDays(45),
    ]);

    $response = $this->actingAs($superadmin)->delete(route('activity-logs.clear'), [
        'days' => 30,
    ]);

    $response->assertRedirect();
    expect(ActivityLog::find($oldLog->id))->toBeNull();
});
