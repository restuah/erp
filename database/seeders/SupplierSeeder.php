<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\TermOfPayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : null;

        $topNet30 = TermOfPayment::where('payment', '30_A_INVOICE')->orWhere('name', 'like', '%30%')->first();
        $topNet45 = TermOfPayment::where('payment', '45_A_INVOICE')->orWhere('name', 'like', '%45%')->first();
        $topNet60 = TermOfPayment::where('payment', '60_A_INVOICE')->orWhere('name', 'like', '%60%')->first();
        $topCod = TermOfPayment::where('payment', 'COD')->first();

        $suppliers = [
            [
                'supplier_code' => 'SPL-DIR-001',
                'supplier_name' => 'PT Krakatau Steel (Persero) Tbk',
                'supplier_type' => 'direct',
                'status' => 'active',
                'term_of_payment_id' => $topNet30?->id,
                'address' => 'Kawasan Industri Krakatau, Jl. Industri No. 5, Cilegon, Banten',
                'zipcode' => '42435',
                'npwp' => '01.000.013.1-051.000',
                'office_phone' => '+62 254 392159',
                'marketing_name' => 'Bambang Sudiro',
                'marketing_phone' => '+62 811 8892 110',
                'marketing_email' => 'sales@krakatausteel.com',
                'accounting_name' => 'Rina Wijaya',
                'accounting_phone' => '+62 812 3456 789',
                'accounting_email' => 'ar-cilegon@krakatausteel.com',
                'bank_name' => 'Bank Mandiri',
                'bank_account_name' => 'PT Krakatau Steel (Persero) Tbk',
                'bank_account_number' => '120-00-1122334-5',
                'default_currency' => 'IDR',
                'country_code' => 'ID',
                'delivery_period' => '3-5 Hari Kerja',
                'delivery_time' => '08:00',
                'delivery_end_time' => '17:00',
                'created_by' => $adminId,
            ],
            [
                'supplier_code' => 'SPL-DIR-002',
                'supplier_name' => 'PT Astra Otoparts Tbk',
                'supplier_type' => 'direct',
                'status' => 'active',
                'term_of_payment_id' => $topNet45?->id ?? $topNet30?->id,
                'address' => 'Jl. Raya Pegangsaan Dua Km. 2,2, Kelapa Gading, Jakarta Utara',
                'zipcode' => '14250',
                'npwp' => '01.303.490.5-045.000',
                'office_phone' => '+62 21 4603550',
                'marketing_name' => 'Hendro Santoso',
                'marketing_phone' => '+62 813 9088 1234',
                'marketing_email' => 'corporate-sales@component.astra.co.id',
                'accounting_name' => 'Dewi Lestari',
                'accounting_phone' => '+62 815 7890 1234',
                'accounting_email' => 'finance@component.astra.co.id',
                'bank_name' => 'Bank Central Asia (BCA)',
                'bank_account_name' => 'PT Astra Otoparts Tbk',
                'bank_account_number' => '001-3456789',
                'default_currency' => 'IDR',
                'country_code' => 'ID',
                'delivery_period' => '2-3 Hari Kerja',
                'delivery_time' => '08:30',
                'delivery_end_time' => '16:30',
                'created_by' => $adminId,
            ],
            [
                'supplier_code' => 'SPL-IND-001',
                'supplier_name' => 'PT Schneider Electric Indonesia',
                'supplier_type' => 'indirect',
                'status' => 'active',
                'term_of_payment_id' => $topNet30?->id,
                'address' => 'Gedung Ventura Lt. 7, Jl. R.A. Kartini No. 26, Cilandak, Jakarta Selatan',
                'zipcode' => '12430',
                'npwp' => '01.061.782.7-062.000',
                'office_phone' => '+62 21 7504406',
                'marketing_name' => 'Christian Pratama',
                'marketing_phone' => '+62 812 1122 3344',
                'marketing_email' => 'christian.p@se.com',
                'accounting_name' => 'Ferry Gunawan',
                'accounting_phone' => '+62 818 2233 4455',
                'accounting_email' => 'id-finance@se.com',
                'bank_name' => 'Bank Danamon',
                'bank_account_name' => 'PT Schneider Electric Indonesia',
                'bank_account_number' => '355-1234-567',
                'default_currency' => 'IDR',
                'country_code' => 'ID',
                'delivery_period' => '5-7 Hari Kerja',
                'delivery_time' => '09:00',
                'delivery_end_time' => '17:00',
                'created_by' => $adminId,
            ],
            [
                'supplier_code' => 'SPL-DIR-003',
                'supplier_name' => 'PT United Tractors Tbk',
                'supplier_type' => 'direct',
                'status' => 'active',
                'term_of_payment_id' => $topNet60?->id ?? $topNet30?->id,
                'address' => 'Jl. Raya Bekasi Km. 22, Cakung, Jakarta Timur',
                'zipcode' => '13910',
                'npwp' => '01.001.625.1-092.000',
                'office_phone' => '+62 21 24579999',
                'marketing_name' => 'Agus Priyono',
                'marketing_phone' => '+62 811 9988 776',
                'marketing_email' => 'heavy.equip@unitedtractors.com',
                'accounting_name' => 'Maya Indah',
                'accounting_phone' => '+62 812 8877 665',
                'accounting_email' => 'ar-heavy@unitedtractors.com',
                'bank_name' => 'Bank Mandiri',
                'bank_account_name' => 'PT United Tractors Tbk',
                'bank_account_number' => '125-00-9988776-1',
                'default_currency' => 'IDR',
                'country_code' => 'ID',
                'delivery_period' => '7-14 Hari Kerja',
                'delivery_time' => '08:00',
                'delivery_end_time' => '16:00',
                'created_by' => $adminId,
            ],
            [
                'supplier_code' => 'SPL-IND-002',
                'supplier_name' => 'PT Telkom Indonesia (Persero) Tbk',
                'supplier_type' => 'indirect',
                'status' => 'active',
                'term_of_payment_id' => $topCod?->id ?? $topNet30?->id,
                'address' => 'Telkom Landmark Tower, Jl. Jend. Gatot Subroto Kav. 52, Jakarta Selatan',
                'zipcode' => '12710',
                'npwp' => '01.000.013.1-093.000',
                'office_phone' => '+62 21 5215103',
                'marketing_name' => 'Satria Nugraha',
                'marketing_phone' => '+62 811 2233 445',
                'marketing_email' => 'enterprise-it@telkom.co.id',
                'accounting_name' => 'Budi Santosa',
                'accounting_phone' => '+62 812 9988 776',
                'accounting_email' => 'billing-ebis@telkom.co.id',
                'bank_name' => 'Bank BNI',
                'bank_account_name' => 'PT Telkom Indonesia Tbk',
                'bank_account_number' => '009-8877-6655',
                'default_currency' => 'IDR',
                'country_code' => 'ID',
                'delivery_period' => 'Layanan Berkelanjutan (SLA 99.9%)',
                'delivery_time' => '00:00',
                'delivery_end_time' => '23:59',
                'created_by' => $adminId,
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::firstOrCreate(
                ['supplier_code' => $supplierData['supplier_code']],
                $supplierData
            );
        }
    }
}
