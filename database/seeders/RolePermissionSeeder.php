<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
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

            // Role management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Permission management
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',

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

            // Master Exchange Rate management
            'exchange_rates.view',
            'exchange_rates.create',
            'exchange_rates.edit',
            'exchange_rates.delete',
            'exchange_rates.sync',

            // Master Budget management
            'budgets.view',
            'budgets.create',
            'budgets.edit',
            'budgets.delete',

            // Master Budget Classification management
            'budget_classifications.view',
            'budget_classifications.create',
            'budget_classifications.edit',
            'budget_classifications.delete',

            // Master Chart of Accounts (COA) management
            'coa.view',
            'coa.create',
            'coa.edit',
            'coa.delete',

            // Master Unit of Measure (UOM) management
            'uom.view',
            'uom.create',
            'uom.edit',
            'uom.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
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
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'User',
            'guard_name' => 'web',
        ]);
        $userRole->syncPermissions([
            'users.view',
        ]);
    }
}
