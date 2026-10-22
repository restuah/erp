<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
});

test('email change triggers otp flow and can be verified', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => 'new-email@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    // OTP was stored in cache
    $cacheKey = 'email_otp_'.$user->id;
    $cacheData = \Illuminate\Support\Facades\Cache::get($cacheKey);
    $this->assertNotNull($cacheData);
    $this->assertSame('new-email@example.com', $cacheData['email']);

    // Set known OTP in cache and verify
    \Illuminate\Support\Facades\Cache::put($cacheKey, [
        'email' => 'new-email@example.com',
        'otp' => \Illuminate\Support\Facades\Hash::make('123456'),
        'attempts' => 0,
        'sent_at' => now()->timestamp,
    ], now()->addMinutes(15));

    $verifyResponse = $this
        ->actingAs($user)
        ->post('/profile/email/verify-otp', [
            'otp' => '123456',
        ]);

    $verifyResponse
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();
    $this->assertSame('new-email@example.com', $user->email);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertSoftDeleted($user);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
