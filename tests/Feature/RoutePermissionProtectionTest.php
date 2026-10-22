<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutePermissionProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->get(route('suppliers.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden_403(): void
    {
        // User with basic 'User' role (which only has 'users.view' from seeder)
        $user = User::factory()->create();
        $userRole = Role::where('name', 'User')->first();
        $user->assignRole($userRole);

        // Accessing suppliers (requires 'suppliers.view') should return 403 Forbidden
        $response = $this->actingAs($user)->get(route('suppliers.index'));
        $response->assertForbidden();

        // Accessing budgets (requires 'budgets.view') should return 403 Forbidden
        $response = $this->actingAs($user)->get(route('budgets.index'));
        $response->assertForbidden();

        // Accessing roles (requires 'roles.view') should return 403 Forbidden
        $response = $this->actingAs($user)->get(route('roles.index'));
        $response->assertForbidden();

        // Accessing recycle bin (requires role 'Superadmin') should return 403 Forbidden
        $response = $this->actingAs($user)->get(route('recycle-bin.index'));
        $response->assertForbidden();
    }

    public function test_user_with_permission_inherited_from_role_is_authorized(): void
    {
        // Create custom role 'Purchasing' with suppliers.view permission
        $role = Role::create(['name' => 'Purchasing', 'guard_name' => 'web']);
        $permission = Permission::where('name', 'suppliers.view')->first();
        $role->givePermissionTo($permission);

        $purchasingUser = User::factory()->create();
        $purchasingUser->assignRole($role);

        // Should be authorized because the role has 'suppliers.view'
        $response = $this->actingAs($purchasingUser)->get(route('suppliers.index'));
        $response->assertOk();

        // But should be 403 for budgets because 'Purchasing' doesn't have 'budgets.view'
        $budgetResponse = $this->actingAs($purchasingUser)->get(route('budgets.index'));
        $budgetResponse->assertForbidden();
    }

    public function test_superadmin_bypasses_all_permission_and_role_checks(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('Superadmin');

        $this->actingAs($superadmin)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($superadmin)->get(route('budgets.index'))->assertOk();
        $this->actingAs($superadmin)->get(route('roles.index'))->assertOk();
        $this->actingAs($superadmin)->get(route('recycle-bin.index'))->assertOk();
    }
}
