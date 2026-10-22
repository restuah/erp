<?php

namespace Database\Seeders;

use App\Models\TermOfPayment;
use Illuminate\Database\Seeder;

class TermOfPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = [
            [
                'payment' => 'COD',
                'name' => 'Cash On Delivery',
                'days' => 0,
                'description' => 'Pembayaran tunai langsung saat barang diterima.',
                'is_active' => true,
            ],
            [
                'payment' => 'CBD',
                'name' => 'Cash Before Delivery',
                'days' => 0,
                'description' => 'Pembayaran lunas sebelum barang dikirim oleh vendor.',
                'is_active' => true,
            ],
            [
                'payment' => '7_A_INVOICE',
                'name' => '7 Days After Invoice',
                'days' => 7,
                'description' => 'Jatuh tempo 7 hari kalender setelah faktur/invoice diterbitkan.',
                'is_active' => true,
            ],
            [
                'payment' => '14_A_INVOICE',
                'name' => '14 Days After Invoice',
                'days' => 14,
                'description' => 'Jatuh tempo 14 hari kalender setelah faktur/invoice diterbitkan.',
                'is_active' => true,
            ],
            [
                'payment' => '30_A_INVOICE',
                'name' => '30 Days After Invoice',
                'days' => 30,
                'description' => 'Jatuh tempo 30 hari kalender setelah faktur/invoice diterbitkan (Net 30).',
                'is_active' => true,
            ],
            [
                'payment' => '45_A_INVOICE',
                'name' => '45 Days After Invoice',
                'days' => 45,
                'description' => 'Jatuh tempo 45 hari kalender setelah faktur/invoice diterbitkan.',
                'is_active' => true,
            ],
            [
                'payment' => '60_A_INVOICE',
                'name' => '60 Days After Invoice',
                'days' => 60,
                'description' => 'Jatuh tempo 60 hari kalender setelah faktur/invoice diterbitkan (Net 60).',
                'is_active' => true,
            ],
            [
                'payment' => '90_A_INVOICE',
                'name' => '90 Days After Invoice',
                'days' => 90,
                'description' => 'Jatuh tempo 90 hari kalender setelah faktur/invoice diterbitkan (Net 90).',
                'is_active' => true,
            ],
        ];

        foreach ($terms as $term) {
            TermOfPayment::updateOrCreate(
                ['payment' => $term['payment']],
                $term
            );
        }
    }
}
