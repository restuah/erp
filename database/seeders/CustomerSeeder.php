<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerBank;
use App\Models\TermOfPayment;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin?->id;

        $topNet30 = TermOfPayment::where('payment', '30_A_INVOICE')->orWhere('name', 'like', '%30%')->first();
        $topNet45 = TermOfPayment::where('payment', '45_A_INVOICE')->orWhere('name', 'like', '%45%')->first();
        $topNet60 = TermOfPayment::where('payment', '60_A_INVOICE')->orWhere('name', 'like', '%60%')->first();

        $currencyIdr = Currency::where('code', 'IDR')->first();
        $currencyUsd = Currency::where('code', 'USD')->first();

        // 1121 - Piutang Usaha Pihak Ketiga
        $coaReceivable = ChartOfAccount::where('account_code', '1121')->first();

        $customers = [
            [
                'customer_code' => 'CUST-0001',
                'name' => 'PT Astra Daihatsu Motor',
                'alias' => 'ADM',
                'tax_id' => '01.302.839.4-007.000',
                'term_of_payment_id' => $topNet45?->id ?? $topNet30?->id,
                'currency_id' => $currencyIdr?->id,
                'coa_receivable_id' => $coaReceivable?->id,
                'phone' => '+62 21 6501234',
                'fax' => '+62 21 6501235',
                'email' => 'purchasing-parts@daihatsu.astra.co.id',
                'contact_person' => 'Dwi Handoko',
                'contact_person_phone' => '+62 811 1234 5678',
                'contact_person_email' => 'dwi.handoko@daihatsu.astra.co.id',
                'billing_address' => 'Jl. Gaya Motor Barat No. 1, Sunter II, Jakarta Utara 14330',
                'shipping_address' => 'Plant Sunter Assembly: Jl. Gaya Motor III No. 5, Sunter II, Jakarta Utara',
                'tax_address' => 'Jl. Gaya Motor Barat No. 1, Sunter II, Tanjung Priok, Jakarta Utara',
                'delivery_lead_time_days' => 2,
                'is_active' => true,
                'notes' => 'Customer utama untuk komponen stamping & welding otomotif.',
                'created_by' => $adminId,
                'bank' => [
                    'bank_name' => 'Bank Central Asia (BCA)',
                    'bank_account_number' => '001-300-8899',
                    'bank_account_name' => 'PT Astra Daihatsu Motor',
                    'bank_branch' => 'KCU Sunter',
                    'swift_code' => 'CENAIDJA',
                    'is_primary' => true,
                ],
            ],
            [
                'customer_code' => 'CUST-0002',
                'name' => 'PT Toyota Motor Manufacturing Indonesia',
                'alias' => 'TMMIN',
                'tax_id' => '01.000.413.3-051.000',
                'term_of_payment_id' => $topNet60?->id ?? $topNet45?->id,
                'currency_id' => $currencyIdr?->id,
                'coa_receivable_id' => $coaReceivable?->id,
                'phone' => '+62 21 6515551',
                'fax' => '+62 21 6515552',
                'email' => 'procurement.vendor@toyota.co.id',
                'contact_person' => 'Budi Santoso',
                'contact_person_phone' => '+62 812 8899 7711',
                'contact_person_email' => 'budi.santoso@toyota.co.id',
                'billing_address' => 'Jl. Yos Sudarso, Sunter II, Jakarta Utara 14330',
                'shipping_address' => 'Plant Karawang I: Kawasan Industri KIIC Lot CC 1-4, Karawang Barat, Jawa Barat',
                'tax_address' => 'Jl. Yos Sudarso, Sunter II, Jakarta Utara 14330',
                'delivery_lead_time_days' => 3,
                'is_active' => true,
                'notes' => 'OEM Tier-1 supply brake & bracket assemblies.',
                'created_by' => $adminId,
                'bank' => [
                    'bank_name' => 'Bank Mandiri',
                    'bank_account_number' => '120-00-9988771-2',
                    'bank_account_name' => 'PT Toyota Motor Manufacturing Indonesia',
                    'bank_branch' => 'KC Jakarta Sunter',
                    'swift_code' => 'BMRIIDJA',
                    'is_primary' => true,
                ],
            ],
            [
                'customer_code' => 'CUST-0003',
                'name' => 'PT Honda Prospect Motor',
                'alias' => 'HPM',
                'tax_id' => '01.001.234.5-432.000',
                'term_of_payment_id' => $topNet30?->id,
                'currency_id' => $currencyIdr?->id,
                'coa_receivable_id' => $coaReceivable?->id,
                'phone' => '+62 267 440777',
                'fax' => '+62 267 440778',
                'email' => 'purchasing.dept@hpm.co.id',
                'contact_person' => 'Eko Prasetyo',
                'contact_person_phone' => '+62 813 5544 3322',
                'contact_person_email' => 'eko.prasetyo@hpm.co.id',
                'billing_address' => 'Jl. Mitra Utara II Kawasan Industri Mitrakarawang, Karawang 41361',
                'shipping_address' => 'Delivery Dock Area 3, Kawasan Industri Mitrakarawang, Karawang Timur',
                'tax_address' => 'Jl. Mitra Utara II Kawasan Industri Mitrakarawang, Karawang 41361',
                'delivery_lead_time_days' => 2,
                'is_active' => true,
                'notes' => 'Pengiriman JIT (Just In Time) harian sesuai kanban schedule.',
                'created_by' => $adminId,
                'bank' => [
                    'bank_name' => 'Bank Mizuho Indonesia',
                    'bank_account_number' => '888-01-234567',
                    'bank_account_name' => 'PT Honda Prospect Motor',
                    'bank_branch' => 'Jakarta Sudirman Branch',
                    'swift_code' => 'MHCBIDJA',
                    'is_primary' => true,
                ],
            ],
            [
                'customer_code' => 'CUST-0004',
                'name' => 'PT Suzuki Indomobil Motor',
                'alias' => 'SIM',
                'tax_id' => '01.300.999.1-011.000',
                'term_of_payment_id' => $topNet45?->id ?? $topNet30?->id,
                'currency_id' => $currencyIdr?->id,
                'coa_receivable_id' => $coaReceivable?->id,
                'phone' => '+62 21 8970101',
                'fax' => '+62 21 8970102',
                'email' => 'procurement.cikarang@suzuki.co.id',
                'contact_person' => 'Siti Nurhaliza',
                'contact_person_phone' => '+62 815 6789 1234',
                'contact_person_email' => 'siti.nurhaliza@suzuki.co.id',
                'billing_address' => 'Kawasan Industri GIIC Blok C-1, Cikarang Pusat, Kab. Bekasi 17530',
                'shipping_address' => 'Plant Cikarang GIIC, Loading Bay B, Cikarang Pusat, Jawa Barat',
                'tax_address' => 'Kawasan Industri GIIC Blok C-1, Cikarang Pusat, Kab. Bekasi 17530',
                'delivery_lead_time_days' => 1,
                'is_active' => true,
                'notes' => 'Supply part chassis dan sub-assembly.',
                'created_by' => $adminId,
                'bank' => [
                    'bank_name' => 'Bank Danamon Indonesia',
                    'bank_account_number' => '003-556-7890',
                    'bank_account_name' => 'PT Suzuki Indomobil Motor',
                    'bank_branch' => 'KCP Cikarang Jababeka',
                    'swift_code' => 'BDINIDJA',
                    'is_primary' => true,
                ],
            ],
            [
                'customer_code' => 'CUST-0005',
                'name' => 'PT Mitsubishi Motors Krama Yudha Indonesia',
                'alias' => 'MMKI',
                'tax_id' => '03.250.777.8-413.000',
                'term_of_payment_id' => $topNet45?->id ?? $topNet30?->id,
                'currency_id' => $currencyIdr?->id,
                'coa_receivable_id' => $coaReceivable?->id,
                'phone' => '+62 21 8984000',
                'fax' => '+62 21 8984001',
                'email' => 'supplier.relation@mmki.co.id',
                'contact_person' => 'Agus Salim',
                'contact_person_phone' => '+62 817 9900 1122',
                'contact_person_email' => 'agus.salim@mmki.co.id',
                'billing_address' => 'Kawasan Industri Greenland International Industrial Center (GIIC) Blok BA No. 1, Pasirranji, Cikarang Pusat, Bekasi 17530',
                'shipping_address' => 'Plant MMKI GIIC Blok BA No. 1, Cikarang Pusat, Bekasi',
                'tax_address' => 'Kawasan Industri GIIC Blok BA No. 1, Cikarang Pusat, Bekasi 17530',
                'delivery_lead_time_days' => 2,
                'is_active' => true,
                'notes' => 'Supply engine mount brackets & suspension arms.',
                'created_by' => $adminId,
                'bank' => [
                    'bank_name' => 'MUFG Bank Ltd',
                    'bank_account_number' => '100-22-334455',
                    'bank_account_name' => 'PT Mitsubishi Motors Krama Yudha Indonesia',
                    'bank_branch' => 'Jakarta Branch',
                    'swift_code' => 'BOTKIDJA',
                    'is_primary' => true,
                ],
            ],
        ];

        foreach ($customers as $data) {
            $bankData = $data['bank'];
            unset($data['bank']);

            $customer = Customer::updateOrCreate(
                ['customer_code' => $data['customer_code']],
                $data
            );

            if ($bankData) {
                CustomerBank::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'bank_account_number' => $bankData['bank_account_number'],
                    ],
                    [
                        'bank_name' => $bankData['bank_name'],
                        'bank_account_name' => $bankData['bank_account_name'],
                        'bank_branch' => $bankData['bank_branch'],
                        'swift_code' => $bankData['swift_code'],
                        'is_primary' => $bankData['is_primary'],
                    ]
                );
            }
        }
    }
}
