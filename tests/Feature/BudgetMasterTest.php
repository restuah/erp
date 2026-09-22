<?php

use App\Models\Budget;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('Superadmin');
});

test('budget master page can be rendered', function () {
    $pic = User::factory()->create(['name' => 'Owner Budget']);
    $checker1 = User::factory()->create(['name' => 'Checker One']);

    $budget = Budget::create([
        'code' => 'BDG-2026-0001',
        'name' => 'Biaya Mold Line 1',
        'pic_id' => $pic->id,
        'is_active' => true,
    ]);

    $budget->checkers()->create([
        'user_id' => $checker1->id,
        'order' => 1,
        'role_title' => 'Checker 1',
    ]);

    $response = $this->actingAs($this->user)->get(route('budgets.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Budgets/Index')
        ->has('budgets.data', 1)
        ->where('budgets.data.0.code', 'BDG-2026-0001')
        ->where('budgets.data.0.name', 'Biaya Mold Line 1')
        ->where('budgets.data.0.pic.name', 'Owner Budget')
        ->has('budgets.data.0.checkers', 1)
    );
});

test('new budget can be created with single checker', function () {
    $pic = User::factory()->create();
    $checker = User::factory()->create();

    $response = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => 'BDG-2026-0002',
        'name' => 'Capex CNC Machine',
        'pic_id' => $pic->id,
        'description' => 'Pembelian mesin CNC',
        'is_active' => true,
        'checkers' => [
            [
                'user_id' => $checker->id,
                'role_title' => 'Supervisor',
            ],
        ],
    ]);

    $response->assertRedirect(route('budgets.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('budgets', [
        'code' => 'BDG-2026-0002',
        'name' => 'Capex CNC Machine',
        'pic_id' => $pic->id,
        'created_by' => $this->user->id,
    ]);

    $budget = Budget::where('code', 'BDG-2026-0002')->first();
    expect($budget->checkers)->toHaveCount(1);
    expect($budget->checkers->first()->user_id)->toBe($checker->id);
    expect($budget->checkers->first()->order)->toBe(1);
});

test('new budget can be created with 3 checkers (Checker 1, 2, 3)', function () {
    $pic = User::factory()->create();
    $c1 = User::factory()->create(['name' => 'Checker SPV']);
    $c2 = User::factory()->create(['name' => 'Checker Manager']);
    $c3 = User::factory()->create(['name' => 'Checker GM']);

    $response = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => 'BDG-2026-0003',
        'name' => 'Operational Injection Molding',
        'pic_id' => $pic->id,
        'is_active' => true,
        'checkers' => [
            ['user_id' => $c1->id, 'role_title' => 'Checker 1 (SPV)'],
            ['user_id' => $c2->id, 'role_title' => 'Checker 2 (Manager)'],
            ['user_id' => $c3->id, 'role_title' => 'Checker 3 (GM)'],
        ],
    ]);

    $response->assertRedirect(route('budgets.index'));
    $budget = Budget::where('code', 'BDG-2026-0003')->first();
    expect($budget->checkers)->toHaveCount(3);
    expect($budget->checkers[0]->order)->toBe(1);
    expect($budget->checkers[0]->user_id)->toBe($c1->id);
    expect($budget->checkers[1]->order)->toBe(2);
    expect($budget->checkers[1]->user_id)->toBe($c2->id);
    expect($budget->checkers[2]->order)->toBe(3);
    expect($budget->checkers[2]->user_id)->toBe($c3->id);
});

test('new budget can be created with more than 3 checkers dynamically', function () {
    $pic = User::factory()->create();
    $c1 = User::factory()->create();
    $c2 = User::factory()->create();
    $c3 = User::factory()->create();
    $c4 = User::factory()->create();
    $c5 = User::factory()->create();

    $response = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => 'BDG-2026-0004',
        'name' => 'Mega Project Factory Extension',
        'pic_id' => $pic->id,
        'is_active' => true,
        'checkers' => [
            ['user_id' => $c1->id, 'role_title' => 'Checker 1 - Line Lead'],
            ['user_id' => $c2->id, 'role_title' => 'Checker 2 - Section Head'],
            ['user_id' => $c3->id, 'role_title' => 'Checker 3 - Dept Manager'],
            ['user_id' => $c4->id, 'role_title' => 'Checker 4 - Division Head'],
            ['user_id' => $c5->id, 'role_title' => 'Checker 5 - Director'],
        ],
    ]);

    $response->assertRedirect(route('budgets.index'));
    $budget = Budget::where('code', 'BDG-2026-0004')->first();
    expect($budget->checkers)->toHaveCount(5);
    expect($budget->checkers[4]->order)->toBe(5);
    expect($budget->checkers[4]->user_id)->toBe($c5->id);
});

test('budget creation validates uniqueness and required fields', function () {
    $pic = User::factory()->create();

    Budget::create([
        'code' => 'BDG-EXISTING',
        'name' => 'Existing Budget',
        'pic_id' => $pic->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => 'BDG-EXISTING',
        'name' => 'Duplicate Code',
        'pic_id' => $pic->id,
    ]);

    $response->assertSessionHasErrors(['code']);

    $responseEmpty = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => '',
        'name' => '',
        'pic_id' => '',
    ]);

    $responseEmpty->assertSessionHasErrors(['code', 'name', 'pic_id']);
});

test('budget creation prevents duplicate users in same checker chain', function () {
    $pic = User::factory()->create();
    $duplicateUser = User::factory()->create();

    $response = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => 'BDG-DUP-CHECKER',
        'name' => 'Budget Test',
        'pic_id' => $pic->id,
        'checkers' => [
            ['user_id' => $duplicateUser->id, 'role_title' => 'Checker 1'],
            ['user_id' => $duplicateUser->id, 'role_title' => 'Checker 2'],
        ],
    ]);

    $response->assertSessionHasErrors(['checkers.0.user_id', 'checkers.1.user_id']);
});

test('budget can be updated with synced checkers', function () {
    $pic = User::factory()->create();
    $c1 = User::factory()->create();
    $c2 = User::factory()->create();
    $c3 = User::factory()->create();

    $budget = Budget::create([
        'code' => 'BDG-UPDATE-01',
        'name' => 'Initial Name',
        'pic_id' => $pic->id,
        'is_active' => true,
    ]);

    $budget->checkers()->create(['user_id' => $c1->id, 'order' => 1, 'role_title' => 'Checker 1']);

    // Update with c2 and c3 replacing c1
    $response = $this->actingAs($this->user)->put(route('budgets.update', $budget->id), [
        'code' => 'BDG-UPDATE-01',
        'name' => 'Updated Name',
        'pic_id' => $pic->id,
        'is_active' => false,
        'checkers' => [
            ['user_id' => $c2->id, 'role_title' => 'New Checker 1'],
            ['user_id' => $c3->id, 'role_title' => 'New Checker 2'],
        ],
    ]);

    $response->assertRedirect(route('budgets.index'));
    $budget->refresh();

    expect($budget->name)->toBe('Updated Name');
    expect($budget->is_active)->toBeFalse();
    expect($budget->checkers)->toHaveCount(2);
    expect($budget->checkers[0]->user_id)->toBe($c2->id);
    expect($budget->checkers[1]->user_id)->toBe($c3->id);
});

test('budget can be soft deleted', function () {
    $pic = User::factory()->create();
    $budget = Budget::create([
        'code' => 'BDG-DELETE-01',
        'name' => 'To be deleted',
        'pic_id' => $pic->id,
    ]);

    $response = $this->actingAs($this->user)->delete(route('budgets.destroy', $budget->id));

    $response->assertRedirect(route('budgets.index'));
    $this->assertSoftDeleted('budgets', [
        'id' => $budget->id,
    ]);
});

test('generate code endpoint returns next sequence', function () {
    $response = $this->actingAs($this->user)->getJson(route('budgets.generate-code'));

    $response->assertOk();
    $response->assertJsonStructure(['code']);
    $year = date('Y');
    expect($response->json('code'))->toStartWith("BDG-{$year}-");
});

test('budget can be created with or without classification', function () {
    $pic = User::factory()->create();
    $classification = \App\Models\BudgetClassification::create([
        'name' => 'CAPEX',
    ]);

    // Test with classification
    $responseWith = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => 'BDG-WITH-CLASS',
        'name' => 'Budget With Classification',
        'budget_classification_id' => $classification->id,
        'pic_id' => $pic->id,
    ]);

    $responseWith->assertRedirect(route('budgets.index'));
    $this->assertDatabaseHas('budgets', [
        'code' => 'BDG-WITH-CLASS',
        'budget_classification_id' => $classification->id,
    ]);

    // Test without classification (nullable)
    $responseWithout = $this->actingAs($this->user)->post(route('budgets.store'), [
        'code' => 'BDG-WITHOUT-CLASS',
        'name' => 'Budget Without Classification',
        'budget_classification_id' => null,
        'pic_id' => $pic->id,
    ]);

    $responseWithout->assertRedirect(route('budgets.index'));
    $this->assertDatabaseHas('budgets', [
        'code' => 'BDG-WITHOUT-CLASS',
        'budget_classification_id' => null,
    ]);
});

