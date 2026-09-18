<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'United States Dollar'],
            ['code' => 'EUR', 'name' => 'Euro'],
            ['code' => 'SGD', 'name' => 'Singapore Dollar'],
            ['code' => 'JPY', 'name' => 'Japanese Yen'],
            ['code' => 'GBP', 'name' => 'British Pound Sterling'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan Renminbi'],
            ['code' => 'AUD', 'name' => 'Australian Dollar'],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                ['name' => $currency['name']]
            );
        }
    }
}
