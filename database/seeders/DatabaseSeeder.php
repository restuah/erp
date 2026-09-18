<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles & Permissions
        $this->call(RolePermissionSeeder::class);

        // 2. Create Superadmin User
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@erp.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superadmin->assignRole('Superadmin');

        // 3. Create Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@erp.test'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('User');

        // 4. Seed Currencies
        $this->call(CurrencySeeder::class);
    }
}
