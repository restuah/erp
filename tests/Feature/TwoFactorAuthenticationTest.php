<?php

use App\Models\User;
use App\Notifications\TwoFactorOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('profile page displays 2fa status', function () {
    $user = User::factory()->create([
        'two_factor_enabled' => false,
    ]);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Profile/Edit')
        ->where('twoFactorEnabled', false)
    );
});

test('user can request 2fa activation otp', function () {
    Notification::fake();

    $user = User::factory()->create([
        'two_factor_enabled' => false,
    ]);

    $response = $this->actingAs($user)->post('/profile/two-factor/enable');

    $response->assertRedirect('/profile');
    $response->assertSessionHas('status', 'two-factor-otp-sent');

    Notification::assertSentTo($user, TwoFactorOtpNotification::class, function ($notification) {
        return $notification->action === 'activation';
    });

    $cached = Cache::get('two_factor_activation_'.$user->id);
    expect($cached)->not->toBeNull();
    expect($cached['attempts'])->toBe(0);
});

test('user cannot confirm 2fa with invalid otp', function () {
    $user = User::factory()->create([
        'two_factor_enabled' => false,
    ]);

    Cache::put('two_factor_activation_'.$user->id, [
        'otp' => Hash::make('123456'),
        'attempts' => 0,
        'sent_at' => now()->timestamp,
    ], now()->addMinutes(15));

    $response = $this->actingAs($user)->post('/profile/two-factor/confirm', [
        'otp' => '654321',
    ]);

    $response->assertSessionHasErrors('otp');
    $user->refresh();
    expect($user->two_factor_enabled)->toBeFalse();

    $cached = Cache::get('two_factor_activation_'.$user->id);
    expect($cached['attempts'])->toBe(1);
});

test('user can confirm 2fa with valid otp', function () {
    $user = User::factory()->create([
        'two_factor_enabled' => false,
    ]);

    Cache::put('two_factor_activation_'.$user->id, [
        'otp' => Hash::make('123456'),
        'attempts' => 0,
        'sent_at' => now()->timestamp,
    ], now()->addMinutes(15));

    $response = $this->actingAs($user)->post('/profile/two-factor/confirm', [
        'otp' => '123456',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/profile');

    $user->refresh();
    expect($user->two_factor_enabled)->toBeTrue();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
    expect(Cache::get('two_factor_activation_'.$user->id))->toBeNull();
});

test('user can resend 2fa activation otp after cooldown', function () {
    Notification::fake();

    $user = User::factory()->create([
        'two_factor_enabled' => false,
    ]);

    // Already passed 60 seconds
    Cache::put('two_factor_activation_'.$user->id, [
        'otp' => Hash::make('111111'),
        'attempts' => 0,
        'sent_at' => now()->subSeconds(65)->timestamp,
    ], now()->addMinutes(15));

    $response = $this->actingAs($user)->post('/profile/two-factor/resend');

    $response->assertRedirect('/profile');
    Notification::assertSentTo($user, TwoFactorOtpNotification::class);
});

test('user cannot resend 2fa activation otp before cooldown expires', function () {
    Notification::fake();

    $user = User::factory()->create([
        'two_factor_enabled' => false,
    ]);

    // Just sent 10 seconds ago
    Cache::put('two_factor_activation_'.$user->id, [
        'otp' => Hash::make('111111'),
        'attempts' => 0,
        'sent_at' => now()->subSeconds(10)->timestamp,
    ], now()->addMinutes(15));

    $response = $this->actingAs($user)->post('/profile/two-factor/resend');

    $response->assertRedirect('/profile');
    $response->assertSessionHas('error');
    Notification::assertNothingSent();
});

test('user can cancel pending 2fa activation', function () {
    $user = User::factory()->create([
        'two_factor_enabled' => false,
    ]);

    Cache::put('two_factor_activation_'.$user->id, [
        'otp' => Hash::make('123456'),
        'attempts' => 0,
        'sent_at' => now()->timestamp,
    ], now()->addMinutes(15));

    $response = $this->actingAs($user)->delete('/profile/two-factor/cancel');

    $response->assertRedirect('/profile');
    expect(Cache::has('two_factor_activation_'.$user->id))->toBeFalse();
});

test('user can disable 2fa with correct password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
        'two_factor_enabled' => true,
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)->delete('/profile/two-factor', [
        'password' => 'secret-password',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/profile');

    $user->refresh();
    expect($user->two_factor_enabled)->toBeFalse();
    expect($user->two_factor_confirmed_at)->toBeNull();
});

test('user cannot disable 2fa with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
        'two_factor_enabled' => true,
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)->delete('/profile/two-factor', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');

    $user->refresh();
    expect($user->two_factor_enabled)->toBeTrue();
});

test('login requires 2fa verification when enabled', function () {
    Notification::fake();

    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
        'two_factor_enabled' => true,
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('two-factor.challenge'));

    Notification::assertSentTo($user, TwoFactorOtpNotification::class, function ($notification) {
        return $notification->action === 'login';
    });

    // 2FA challenge screen can be rendered
    $challengeResponse = $this->withSession([
        'login.two_factor_user_id' => $user->id,
    ])->get(route('two-factor.challenge'));

    $challengeResponse->assertOk();

    // Verifying valid OTP completes login
    Cache::put('two_factor_login_'.$user->id, [
        'otp' => Hash::make('998877'),
        'attempts' => 0,
        'sent_at' => now()->timestamp,
    ], now()->addMinutes(15));

    $verifyResponse = $this->withSession([
        'login.two_factor_user_id' => $user->id,
    ])->post(route('two-factor.verify'), [
        'otp' => '998877',
    ]);

    $this->assertAuthenticatedAs($user);
    $verifyResponse->assertRedirect(route('dashboard', absolute: false));
});
