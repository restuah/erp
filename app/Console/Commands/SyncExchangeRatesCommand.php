<?php

namespace App\Console\Commands;

use App\Services\BankIndonesiaRateService;
use Illuminate\Console\Command;

class SyncExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:sync 
                            {--start-date= : Tanggal awal sinkronisasi (format: YYYY-MM-DD)} 
                            {--end-date= : Tanggal akhir sinkronisasi (format: YYYY-MM-DD)} 
                            {--currency=* : Kode mata uang tertentu (misal: USD, JPY)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi kurs mata uang asing terhadap IDR dari Web Service Bank Indonesia';

    /**
     * Execute the console command.
     */
    public function handle(BankIndonesiaRateService $service): int
    {
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $currencies = $this->option('currency');

        $this->info('Memulai sinkronisasi kurs Bank Indonesia...');
        if ($startDate || $endDate) {
            $this->comment("Rentang tanggal: " . ($startDate ?? '7 hari lalu') . " s/d " . ($endDate ?? 'hari ini'));
        }
        if (!empty($currencies)) {
            $this->comment("Mata uang: " . implode(', ', $currencies));
        }

        $result = $service->syncRates(
            currencyCodes: !empty($currencies) ? $currencies : null,
            startDate: $startDate,
            endDate: $endDate,
            userId: null // System/cron execution
        );

        if ($result['success']) {
            $this->info("Sinkronisasi selesai. Berhasil memperbarui {$result['synced_count']} catatan kurs dari {$result['currencies_processed']} mata uang.");

            if (!empty($result['errors'])) {
                $this->warn('Peringatan:');
                foreach ($result['errors'] as $error) {
                    $this->line(" - {$error}");
                }
            }

            return self::SUCCESS;
        }

        $this->error('Sinkronisasi gagal.');
        return self::FAILURE;
    }
}
