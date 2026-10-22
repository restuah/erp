<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Standard permissions list
        $permissions = [
            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.restore',
            'users.force_delete',

            // Role management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.restore',
            'roles.force_delete',

            // Permission management
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'permissions.restore',
            'permissions.force_delete',

            // Activity Log management
            'activity_logs.view',
            'activity_logs.delete',

            // Master Calendar management
            'calendar.view',
            'calendar.edit',
            'calendar.sync',

            // Master Currency management
            'currencies.view',
            'currencies.create',
            'currencies.edit',
            'currencies.delete',
            'currencies.restore',
            'currencies.force_delete',

            // Master Exchange Rate management
            'exchange_rates.view',
            'exchange_rates.create',
            'exchange_rates.edit',
            'exchange_rates.delete',
            'exchange_rates.sync',
            'exchange_rates.restore',
            'exchange_rates.force_delete',

            // Master Budget management
            'budgets.view',
            'budgets.create',
            'budgets.edit',
            'budgets.delete',
            'budgets.restore',
            'budgets.force_delete',

            // Master Budget Classification management
            'budget_classifications.view',
            'budget_classifications.create',
            'budget_classifications.edit',
            'budget_classifications.delete',
            'budget_classifications.restore',
            'budget_classifications.force_delete',

            // Master Chart of Accounts (COA) management
            'coa.view',
            'coa.create',
            'coa.edit',
            'coa.delete',
            'coa.restore',
            'coa.force_delete',

            // Master Unit of Measure (UOM) management
            'uom.view',
            'uom.create',
            'uom.edit',
            'uom.delete',
            'uom.restore',
            'uom.force_delete',

            // Master Term of Payment (TOP) management
            'term_of_payments.view',
            'term_of_payments.create',
            'term_of_payments.edit',
            'term_of_payments.delete',
            'term_of_payments.restore',
            'term_of_payments.force_delete',

            // Master Supplier management
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'suppliers.restore',
            'suppliers.force_delete',

            // Master Department management
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',
            'departments.restore',
            'departments.force_delete',

            // Master Customer management
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
            'customers.restore',
            'customers.force_delete',

            // Recycle Bin general access
            'recycle_bin.view',
        ];

        // Batch insert missing permissions to avoid N+1 query overhead
        $existing = Permission::where('guard_name', 'web')->pluck('name')->flip();
        $now = now();
        $missing = collect($permissions)
            ->reject(fn ($name) => isset($existing[$name]))
            ->map(fn ($name) => [
                'id' => (string) Str::uuid(),
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if (!empty($missing)) {
            Permission::insert($missing);
        }

        // Create Roles
        $superadminRole = Role::firstOrCreate([
            'name' => 'Superadmin',
            'guard_name' => 'web',
        ]);
        // Give all permissions to Superadmin
        $superadminRole->syncPermissions(Permission::all());

        $adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',
            'roles.view',
            'permissions.view',
            'activity_logs.view',
            'calendar.view',
            'calendar.edit',
            'calendar.sync',
            'currencies.view',
            'currencies.create',
            'currencies.edit',
            'currencies.delete',
            'exchange_rates.view',
            'exchange_rates.create',
            'exchange_rates.edit',
            'exchange_rates.delete',
            'exchange_rates.sync',
            'budgets.view',
            'budgets.create',
            'budgets.edit',
            'budgets.delete',
            'budget_classifications.view',
            'budget_classifications.create',
            'budget_classifications.edit',
            'budget_classifications.delete',
            'coa.view',
            'coa.create',
            'coa.edit',
            'coa.delete',
            'uom.view',
            'uom.create',
            'uom.edit',
            'uom.delete',
            'term_of_payments.view',
            'term_of_payments.create',
            'term_of_payments.edit',
            'term_of_payments.delete',
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'User',
            'guard_name' => 'web',
        ]);
        $userRole->syncPermissions([]);
    }
}
