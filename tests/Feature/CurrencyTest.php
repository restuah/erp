<?php

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('Superadmin');
});

test('currency master page can be rendered', function () {
    Currency::create([
        'code' => 'USD',
        'name' => 'United States Dollar',
    ]);

    $response = $this->actingAs($this->user)->get(route('currencies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Currencies/Index')
        ->has('currencies.data', 1)
        ->where('currencies.data.0.code', 'USD')
        ->where('currencies.data.0.name', 'United States Dollar')
    );
});

test('new currency can be created', function () {
    $response = $this->actingAs($this->user)->post(route('currencies.store'), [
        'code' => 'jpy',
        'name' => 'Japanese Yen',
    ]);

    $response->assertRedirect(route('currencies.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('currencies', [
        'code' => 'JPY',
        'name' => 'Japanese Yen',
        'created_by' => $this->user->id,
    ]);
});

test('currency creation validates uniqueness and required fields', function () {
    Currency::create([
        'code' => 'EUR',
        'name' => 'Euro',
    ]);

    $response = $this->actingAs($this->user)->post(route('currencies.store'), [
        'code' => 'EUR',
        'name' => 'Euro Duplicate',
    ]);

    $response->assertSessionHasErrors(['code']);

    $responseEmpty = $this->actingAs($this->user)->post(route('currencies.store'), [
        'code' => '',
        'name' => '',
    ]);

    $responseEmpty->assertSessionHasErrors(['code', 'name']);
});

test('currency can be updated', function () {
    $currency = Currency::create([
        'code' => 'SGD',
        'name' => 'Singapore Dollar',
    ]);

    $response = $this->actingAs($this->user)->put(route('currencies.update', $currency->id), [
        'code' => 'SGD',
        'name' => 'Singapore Dollar (Updated)',
    ]);

    $response->assertRedirect(route('currencies.index'));
    $response->assertSessionHas('success');

    $currency->refresh();
    expect($currency->name)->toBe('Singapore Dollar (Updated)');
    expect($currency->updated_by)->toBe($this->user->id);
});

test('currency can be soft deleted', function () {
    $currency = Currency::create([
        'code' => 'GBP',
        'name' => 'British Pound',
    ]);

    $response = $this->actingAs($this->user)->delete(route('currencies.destroy', $currency->id));

    $response->assertRedirect(route('currencies.index'));
    $response->assertSessionHas('success');

    $this->assertSoftDeleted('currencies', [
        'id' => $currency->id,
        'code' => 'GBP',
    ]);
});

test('soft-deleted currency can be restored and force deleted from recycle bin', function () {
    $currency = Currency::create([
        'code' => 'AUD',
        'name' => 'Australian Dollar',
    ]);
    $currency->delete();

    // Verify in recycle bin
    $resBin = $this->actingAs($this->user)->get(route('recycle-bin.index', ['type' => 'currencies']));
    $resBin->assertOk();

    // Restore
    $resRestore = $this->actingAs($this->user)->post(route('recycle-bin.restore', [
        'type' => 'currencies',
        'id' => $currency->id,
    ]));
    $resRestore->assertRedirect();
    $this->assertNotSoftDeleted('currencies', ['id' => $currency->id]);

    // Delete again and Force delete
    $currency->delete();
    $resForce = $this->actingAs($this->user)->delete(route('recycle-bin.force-delete', [
        'type' => 'currencies',
        'id' => $currency->id,
    ]));
    $resForce->assertRedirect();
    $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
});
