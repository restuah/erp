<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Kuantitas / Unit (Count)
            [
                'code' => 'PCS',
                'name' => 'Pieces / Buah',
                'symbol' => 'pcs',
                'category' => 'count',
                'description' => 'Satuan hitung umum per satuan unit atau buah.',
                'is_active' => true,
            ],
            [
                'code' => 'BOX',
                'name' => 'Box / Kotak',
                'symbol' => 'box',
                'category' => 'count',
                'description' => 'Satuan kemasan kotak atau kardus.',
                'is_active' => true,
            ],
            [
                'code' => 'PACK',
                'name' => 'Pack / Bungkus',
                'symbol' => 'pack',
                'category' => 'count',
                'description' => 'Satuan kemasan pack atau bungkus.',
                'is_active' => true,
            ],
            [
                'code' => 'SET',
                'name' => 'Set',
                'symbol' => 'set',
                'category' => 'count',
                'description' => 'Satuan himpunan/kelompok item yang menjadi satu kesatuan.',
                'is_active' => true,
            ],
            [
                'code' => 'UNIT',
                'name' => 'Unit',
                'symbol' => 'unit',
                'category' => 'count',
                'description' => 'Satuan per unit tunggal mesin, perangkat, atau barang.',
                'is_active' => true,
            ],
            [
                'code' => 'ROLL',
                'name' => 'Roll / Gulungan',
                'symbol' => 'roll',
                'category' => 'count',
                'description' => 'Satuan gulungan (kabel, plastik, kain, dll).',
                'is_active' => true,
            ],
            [
                'code' => 'CAN',
                'name' => 'Can / Kaleng',
                'symbol' => 'can',
                'category' => 'count',
                'description' => 'Satuan kemasan kaleng.',
                'is_active' => true,
            ],
            [
                'code' => 'DOZ',
                'name' => 'Dozen / Lusin',
                'symbol' => 'dz',
                'category' => 'count',
                'description' => 'Satuan per 12 buah (lusin).',
                'is_active' => true,
            ],
            [
                'code' => 'RIM',
                'name' => 'Rim (500 Lembar)',
                'symbol' => 'rim',
                'category' => 'count',
                'description' => 'Satuan kertas cetak (500 lembar).',
                'is_active' => true,
            ],

            // Berat (Weight)
            [
                'code' => 'KG',
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'category' => 'weight',
                'description' => 'Satuan baku berat metrik kilogram.',
                'is_active' => true,
            ],
            [
                'code' => 'G',
                'name' => 'Gram',
                'symbol' => 'g',
                'category' => 'weight',
                'description' => 'Satuan berat gram (1/1000 kg).',
                'is_active' => true,
            ],
            [
                'code' => 'MG',
                'name' => 'Milligram',
                'symbol' => 'mg',
                'category' => 'weight',
                'description' => 'Satuan berat miligram.',
                'is_active' => true,
            ],
            [
                'code' => 'TON',
                'name' => 'Ton (Metrik Ton)',
                'symbol' => 't',
                'category' => 'weight',
                'description' => 'Satuan berat 1.000 kilogram.',
                'is_active' => true,
            ],

            // Panjang (Length)
            [
                'code' => 'M',
                'name' => 'Meter',
                'symbol' => 'm',
                'category' => 'length',
                'description' => 'Satuan baku panjang metrik meter.',
                'is_active' => true,
            ],
            [
                'code' => 'CM',
                'name' => 'Centimeter',
                'symbol' => 'cm',
                'category' => 'length',
                'description' => 'Satuan panjang sentimeter (1/100 m).',
                'is_active' => true,
            ],
            [
                'code' => 'MM',
                'name' => 'Millimeter',
                'symbol' => 'mm',
                'category' => 'length',
                'description' => 'Satuan panjang milimeter (1/1000 m).',
                'is_active' => true,
            ],
            [
                'code' => 'KM',
                'name' => 'Kilometer',
                'symbol' => 'km',
                'category' => 'length',
                'description' => 'Satuan jarak kilometer (1.000 meter).',
                'is_active' => true,
            ],
            [
                'code' => 'INCH',
                'name' => 'Inci (Inch)',
                'symbol' => 'in',
                'category' => 'length',
                'description' => 'Satuan panjang imperial inci (2.54 cm).',
                'is_active' => true,
            ],

            // Volume (Volume / Cairan)
            [
                'code' => 'LTR',
                'name' => 'Liter',
                'symbol' => 'L',
                'category' => 'volume',
                'description' => 'Satuan baku volume zat cair atau gas.',
                'is_active' => true,
            ],
            [
                'code' => 'ML',
                'name' => 'Milliliter',
                'symbol' => 'mL',
                'category' => 'volume',
                'description' => 'Satuan volume mililiter (1/1000 liter).',
                'is_active' => true,
            ],
            [
                'code' => 'M3',
                'name' => 'Meter Kubik (CBM)',
                'symbol' => 'm³',
                'category' => 'volume',
                'description' => 'Satuan volume kubikasi kargo / ruang.',
                'is_active' => true,
            ],

            // Luas (Area)
            [
                'code' => 'M2',
                'name' => 'Meter Persegi',
                'symbol' => 'm²',
                'category' => 'area',
                'description' => 'Satuan luas area meter persegi.',
                'is_active' => true,
            ],
            [
                'code' => 'CM2',
                'name' => 'Centimeter Persegi',
                'symbol' => 'cm²',
                'category' => 'area',
                'description' => 'Satuan luas sentimeter persegi.',
                'is_active' => true,
            ],

            // Waktu (Time)
            [
                'code' => 'HR',
                'name' => 'Jam (Hour)',
                'symbol' => 'jam',
                'category' => 'time',
                'description' => 'Satuan waktu jam kerja / penggunaan alat.',
                'is_active' => true,
            ],
            [
                'code' => 'DAY',
                'name' => 'Hari (Day)',
                'symbol' => 'hari',
                'category' => 'time',
                'description' => 'Satuan waktu hari kalender / sewa.',
                'is_active' => true,
            ],
            [
                'code' => 'MONTH',
                'name' => 'Bulan (Month)',
                'symbol' => 'bln',
                'category' => 'time',
                'description' => 'Satuan waktu periode bulanan.',
                'is_active' => true,
            ],
            [
                'code' => 'YR',
                'name' => 'Tahun (Year)',
                'symbol' => 'thn',
                'category' => 'time',
                'description' => 'Satuan waktu periode tahunan.',
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            UnitOfMeasure::firstOrCreate(
                ['code' => $unit['code']],
                $unit
            );
        }
    }
}
