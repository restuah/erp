<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('user can upload and remove avatar from profile', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $avatar = UploadedFile::fake()->image('my-avatar.png', 200, 200);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => $avatar,
    ]);

    $response->assertRedirect(route('profile.edit'));
    $user->refresh();

    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);
    expect($user->avatar_url)->toContain('/storage/' . $user->avatar);

    // Remove avatar
    $response2 = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'remove_avatar' => true,
    ]);

    $response2->assertRedirect(route('profile.edit'));
    $user->refresh();

    expect($user->avatar)->toBeNull();
    expect($user->avatar_url)->toBeNull();
});
