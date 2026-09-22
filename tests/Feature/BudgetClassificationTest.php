<?php

use App\Models\BudgetClassification;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('Superadmin');
});

test('budget classification index page can be rendered', function () {
    BudgetClassification::create([
        'name' => 'CAPEX',
        'description' => 'Capital Expenditure',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->get(route('budget-classifications.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('BudgetClassifications/Index')
        ->has('classifications.data', 1)
        ->where('classifications.data.0.name', 'CAPEX')
        ->where('classifications.data.0.description', 'Capital Expenditure')
    );
});

test('new budget classification can be created', function () {
    $response = $this->actingAs($this->user)->post(route('budget-classifications.store'), [
        'name' => 'OPEX',
        'description' => 'Operational Expenditure',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('budget-classifications.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('budget_classifications', [
        'name' => 'OPEX',
        'description' => 'Operational Expenditure',
        'created_by' => $this->user->id,
    ]);
});

test('budget classification creation validates required name and uniqueness', function () {
    BudgetClassification::create([
        'name' => 'Maintenance',
    ]);

    $responseDuplicate = $this->actingAs($this->user)->post(route('budget-classifications.store'), [
        'name' => 'Maintenance',
    ]);

    $responseDuplicate->assertSessionHasErrors(['name']);

    $responseEmpty = $this->actingAs($this->user)->post(route('budget-classifications.store'), [
        'name' => '',
    ]);

    $responseEmpty->assertSessionHasErrors(['name']);
});

test('budget classification can be updated', function () {
    $classification = BudgetClassification::create([
        'name' => 'R&D Initial',
        'description' => 'Research',
    ]);

    $response = $this->actingAs($this->user)->put(route('budget-classifications.update', $classification->id), [
        'name' => 'Research & Development',
        'description' => 'Updated description',
        'is_active' => false,
    ]);

    $response->assertRedirect(route('budget-classifications.index'));
    $classification->refresh();

    expect($classification->name)->toBe('Research & Development');
    expect($classification->is_active)->toBeFalse();
});

test('budget classification can be soft deleted and restored', function () {
    $classification = BudgetClassification::create([
        'name' => 'To Delete',
    ]);

    $response = $this->actingAs($this->user)->delete(route('budget-classifications.destroy', $classification->id));

    $response->assertRedirect(route('budget-classifications.index'));
    $this->assertSoftDeleted('budget_classifications', [
        'id' => $classification->id,
    ]);

    // Restore via recycle bin
    $restoreResponse = $this->actingAs($this->user)->post(
        route('recycle-bin.restore', ['type' => 'budget_classifications', 'id' => $classification->id])
    );

    $restoreResponse->assertRedirect();
    $this->assertNotSoftDeleted('budget_classifications', [
        'id' => $classification->id,
    ]);
});
