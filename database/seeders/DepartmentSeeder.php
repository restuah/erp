<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::where('email', 'superadmin@erp.test')->first();
        $adminId = $superadmin?->id;

        $departments = [
            [
                'code' => 'ENG',
                'name' => 'Engineering',
                'description' => 'Departemen rekayasa teknis & proses produksi',
                'is_active' => true,
            ],
            [
                'code' => 'FIN ACC',
                'name' => 'Finance & Accounting',
                'description' => 'Departemen keuangan, akuntansi, dan perpajakan',
                'is_active' => true,
            ],
            [
                'code' => 'HRDGA',
                'name' => 'HR & General Affairs',
                'description' => 'Departemen sumber daya manusia dan urusan umum',
                'is_active' => true,
            ],
            [
                'code' => 'MKT',
                'name' => 'Marketing',
                'description' => 'Departemen pemasaran dan hubungan pelanggan',
                'is_active' => true,
            ],
            [
                'code' => 'PE',
                'name' => 'Production Engineering',
                'description' => 'Departemen rekayasa fasilitas & lini produksi',
                'is_active' => true,
            ],
            [
                'code' => 'PPIC',
                'name' => 'Production Planning & Inventory Control',
                'description' => 'Departemen perencanaan produksi & kontrol persediaan material',
                'is_active' => true,
            ],
            [
                'code' => 'PRD',
                'name' => 'Production',
                'description' => 'Departemen manufaktur & operasional lantai pabrik',
                'is_active' => true,
            ],
            [
                'code' => 'QA',
                'name' => 'Quality Assurance & Quality Control',
                'description' => 'Departemen penjaminan & pengendalian mutu produk',
                'is_active' => true,
            ],
            [
                'code' => 'PURCH',
                'name' => 'Purchasing & Procurement',
                'description' => 'Departemen pengadaan barang direct & indirect',
                'is_active' => true,
            ],
            [
                'code' => 'MTC',
                'name' => 'Maintenance',
                'description' => 'Departemen perawatan & perbaikan mesin operasional',
                'is_active' => true,
            ],
            [
                'code' => 'SALES',
                'name' => 'Sales',
                'description' => 'Departemen penjualan & pengiriman produk',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'is_active' => $dept['is_active'],
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
        }
    }
}
