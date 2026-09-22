<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin?->id;

        $accounts = [
            // 1000 - ASET
            ['account_code' => '1000', 'account_name' => 'Aset', 'parent_code' => null, 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Seluruh sumber daya ekonomi yang dimiliki entitas.'],
            ['account_code' => '1100', 'account_name' => 'Aset Lancar', 'parent_code' => '1000', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Aset yang dapat dicairkan dalam waktu satu tahun.'],
            ['account_code' => '1110', 'account_name' => 'Kas dan Setara Kas', 'parent_code' => '1100', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Alat pembayaran yang siap digunakan.'],
            ['account_code' => '1111', 'account_name' => 'Kas Operasional', 'parent_code' => '1110', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Kas tunai harian di kantor.'],
            ['account_code' => '1112', 'account_name' => 'Kas Kecil (Petty Cash)', 'parent_code' => '1110', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Dana kas kecil untuk pengeluaran minor.'],
            ['account_code' => '1113', 'account_name' => 'Bank BCA IDR', 'parent_code' => '1110', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Rekening Giro BCA Rupiah.'],
            ['account_code' => '1114', 'account_name' => 'Bank Mandiri IDR', 'parent_code' => '1110', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Rekening Giro Mandiri Rupiah.'],
            ['account_code' => '1120', 'account_name' => 'Piutang Usaha', 'parent_code' => '1100', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Tagihan kepada pelanggan.'],
            ['account_code' => '1121', 'account_name' => 'Piutang Usaha Pihak Ketiga', 'parent_code' => '1120', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Piutang dagang penjualan.'],
            ['account_code' => '1122', 'account_name' => 'Cadangan Kerugian Piutang', 'parent_code' => '1120', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Akun kontra piutang tak tertagih.'],
            ['account_code' => '1130', 'account_name' => 'Persediaan', 'parent_code' => '1100', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Nilai barang dagang siap jual.'],
            ['account_code' => '1131', 'account_name' => 'Persediaan Barang Dagang', 'parent_code' => '1130', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Barang persediaan di gudang.'],
            ['account_code' => '1200', 'account_name' => 'Aset Tetap', 'parent_code' => '1000', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Aset berwujud jangka panjang.'],
            ['account_code' => '1210', 'account_name' => 'Tanah dan Bangunan', 'parent_code' => '1200', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Properti tanah dan gedung usaha.'],
            ['account_code' => '1211', 'account_name' => 'Tanah', 'parent_code' => '1210', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Hak tanah tempat usaha.'],
            ['account_code' => '1212', 'account_name' => 'Bangunan Gedung', 'parent_code' => '1210', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Gedung kantor dan operasional.'],
            ['account_code' => '1213', 'account_name' => 'Akumulasi Penyusutan Bangunan', 'parent_code' => '1210', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Akumulasi depresiasi gedung.'],
            ['account_code' => '1220', 'account_name' => 'Kendaraan & Inventaris', 'parent_code' => '1200', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Kendaraan dan peralatan kantor.'],
            ['account_code' => '1221', 'account_name' => 'Kendaraan Operasional', 'parent_code' => '1220', 'jenis' => 'debit', 'kategori' => 'bs', 'description' => 'Armada kendaraan kerja.'],
            ['account_code' => '1222', 'account_name' => 'Akumulasi Penyusutan Kendaraan', 'parent_code' => '1220', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Akumulasi depresiasi kendaraan.'],

            // 2000 - LIABILITAS
            ['account_code' => '2000', 'account_name' => 'Liabilitas', 'parent_code' => null, 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Kewajiban masa kini yang timbul dari peristiwa masa lalu.'],
            ['account_code' => '2100', 'account_name' => 'Liabilitas Jangka Pendek', 'parent_code' => '2000', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Kewajiban yang jatuh tempo dalam 1 tahun.'],
            ['account_code' => '2110', 'account_name' => 'Utang Usaha', 'parent_code' => '2100', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Utang kepada supplier / pemasok.'],
            ['account_code' => '2120', 'account_name' => 'Utang Gaji & Upah', 'parent_code' => '2100', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Akrual kewajiban gaji karyawan.'],
            ['account_code' => '2130', 'account_name' => 'Utang Pajak (PPN & PPh)', 'parent_code' => '2100', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Kewajiban perpajakan yang belum disetor.'],
            ['account_code' => '2200', 'account_name' => 'Liabilitas Jangka Panjang', 'parent_code' => '2000', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Kewajiban yang jatuh tempo lebih dari 1 tahun.'],
            ['account_code' => '2210', 'account_name' => 'Utang Bank Jangka Panjang', 'parent_code' => '2200', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Pinjaman modal investasi bank.'],

            // 3000 - EKUITAS
            ['account_code' => '3000', 'account_name' => 'Ekuitas', 'parent_code' => null, 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Hak residual atas aset setelah dikurangi seluruh liabilitas.'],
            ['account_code' => '3100', 'account_name' => 'Modal Saham Disetor', 'parent_code' => '3000', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Modal disetor para pendiri.'],
            ['account_code' => '3200', 'account_name' => 'Saldo Laba Ditahan', 'parent_code' => '3000', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Akumulasi laba tahun-tahun sebelumnya.'],
            ['account_code' => '3300', 'account_name' => 'Laba Periode Berjalan', 'parent_code' => '3000', 'jenis' => 'credit', 'kategori' => 'bs', 'description' => 'Laba / rugi tahun buku berjalan.'],

            // 4000 - PENDAPATAN
            ['account_code' => '4000', 'account_name' => 'Pendapatan', 'parent_code' => null, 'jenis' => 'credit', 'kategori' => 'pl', 'description' => 'Kenaikan manfaat ekonomi selama periode akuntansi.'],
            ['account_code' => '4100', 'account_name' => 'Pendapatan Operasional Usaha', 'parent_code' => '4000', 'jenis' => 'credit', 'kategori' => 'pl', 'description' => 'Penerimaan utama dari penjualan produk & jasa.'],
            ['account_code' => '4110', 'account_name' => 'Pendapatan Penjualan Produk', 'parent_code' => '4100', 'jenis' => 'credit', 'kategori' => 'pl', 'description' => 'Hasil penjualan barang fisik.'],
            ['account_code' => '4120', 'account_name' => 'Pendapatan Jasa & Layanan', 'parent_code' => '4100', 'jenis' => 'credit', 'kategori' => 'pl', 'description' => 'Hasil penjualan jasa atau konsultasi.'],
            ['account_code' => '4130', 'account_name' => 'Diskon & Retur Penjualan', 'parent_code' => '4100', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Akun kontra pendapatan penjualan.'],
            ['account_code' => '4200', 'account_name' => 'Pendapatan Non-Operasional', 'parent_code' => '4000', 'jenis' => 'credit', 'kategori' => 'pl', 'description' => 'Pendapatan di luar kegiatan pokok usaha.'],
            ['account_code' => '4210', 'account_name' => 'Pendapatan Bunga Bank', 'parent_code' => '4200', 'jenis' => 'credit', 'kategori' => 'pl', 'description' => 'Hasil bunga giro atau deposito.'],

            // 5000 - BEBAN POKOK PENJUALAN
            ['account_code' => '5000', 'account_name' => 'Beban Pokok Penjualan', 'parent_code' => null, 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Biaya langsung yang timbul untuk menghasilkan barang/jasa.'],
            ['account_code' => '5100', 'account_name' => 'Harga Pokok Penjualan (HPP)', 'parent_code' => '5000', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Beban pokok persediaan yang terjual.'],

            // 6000 - BEBAN OPERASIONAL
            ['account_code' => '6000', 'account_name' => 'Beban Operasional', 'parent_code' => null, 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Pengeluaran untuk mendukung kegiatan operasional.'],
            ['account_code' => '6100', 'account_name' => 'Beban Gaji dan Personalia', 'parent_code' => '6000', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Biaya kompensasi tenaga kerja.'],
            ['account_code' => '6110', 'account_name' => 'Beban Gaji Karyawan', 'parent_code' => '6100', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Gaji pokok dan uang makan karyawan.'],
            ['account_code' => '6120', 'account_name' => 'Beban BPJS Ketenagakerjaan & Kesehatan', 'parent_code' => '6100', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Iuran jaminan sosial tenaga kerja.'],
            ['account_code' => '6200', 'account_name' => 'Beban Umum & Administrasi', 'parent_code' => '6000', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Pengeluaran umum perkantoran.'],
            ['account_code' => '6210', 'account_name' => 'Beban Listrik, Air & Internet', 'parent_code' => '6200', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Biaya utilitas kantor bulanan.'],
            ['account_code' => '6220', 'account_name' => 'Beban Sewa Kantor', 'parent_code' => '6200', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Biaya amortisasi/sewa tempat usaha.'],
            ['account_code' => '6230', 'account_name' => 'Beban Perlengkapan & ATK', 'parent_code' => '6200', 'jenis' => 'debit', 'kategori' => 'pl', 'description' => 'Alat tulis kantor dan barang habis pakai.'],
        ];

        // First pass: create all accounts with temporary level and postable
        $codeMap = [];
        foreach ($accounts as $item) {
            $coa = ChartOfAccount::firstOrCreate(
                ['account_code' => $item['account_code']],
                [
                    'account_name' => $item['account_name'],
                    'parent_code' => $item['parent_code'],
                    'jenis' => $item['jenis'],
                    'kategori' => $item['kategori'],
                    'description' => $item['description'],
                    'level' => 1,
                    'postable' => true,
                    'is_active' => true,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
            $codeMap[$item['account_code']] = $coa;
        }

        // Second pass: link parent_id and calculate levels
        foreach ($accounts as $item) {
            $coa = $codeMap[$item['account_code']];
            if ($item['parent_code'] && isset($codeMap[$item['parent_code']])) {
                $parent = $codeMap[$item['parent_code']];
                $coa->parent_id = $parent->id;
                $coa->level = $parent->level + 1;
                $coa->saveQuietly();
            } else {
                $coa->parent_id = null;
                $coa->level = 1;
                $coa->saveQuietly();
            }
        }

        // Third pass: auto-update postable status. Accounts with children MUST have postable = false
        $all = ChartOfAccount::all();
        foreach ($all as $item) {
            $hasChildren = ChartOfAccount::where('parent_id', $item->id)->exists();
            $item->postable = !$hasChildren;
            $item->saveQuietly();
        }
    }
}
