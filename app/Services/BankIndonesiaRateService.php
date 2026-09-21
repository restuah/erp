<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankIndonesiaRateService
{
    /**
     * Endpoint API Bank Indonesia Kurs Transaksi.
     */
    protected string $apiUrl = 'https://www.bi.go.id/biwebservice/wskursbi.asmx/getSubKursLokal3';

    /**
     * Sinkronisasi data kurs dari Bank Indonesia untuk mata uang tertentu atau seluruh mata uang di DB.
     *
     * @param array<string>|null $currencyCodes
     * @return array{
     *     success: bool,
     *     synced_count: int,
     *     currencies_processed: int,
     *     errors: array<string>
     * }
     */
    public function syncRates(
        ?array $currencyCodes = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $userId = null
    ): array {
        $startDate = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        // Ambil mata uang dari database yang bukan IDR
        $currencyQuery = Currency::query()->where('code', '!=', 'IDR');
        if (!empty($currencyCodes)) {
            $currencyQuery->whereIn('code', array_map('strtoupper', $currencyCodes));
        }

        $currencies = $currencyQuery->get()->keyBy('code');

        if ($currencies->isEmpty()) {
            return [
                'success' => true,
                'synced_count' => 0,
                'currencies_processed' => 0,
                'errors' => ['Tidak ada mata uang yang terdaftar untuk disinkronkan.'],
            ];
        }

        $totalSynced = 0;
        $currenciesProcessed = 0;
        $errors = [];

        foreach ($currencies as $code => $currency) {
            try {
                // Berikan jeda kecil untuk menghindari rate limiting/WAF BI
                usleep(300000); // 300ms

                $response = Http::retry(3, 800)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/xml, text/xml, */*',
                    ])
                    ->timeout(20)
                    ->get($this->apiUrl, [
                        'mts' => $code,
                        'startdate' => $startDate,
                        'enddate' => $endDate,
                    ]);

                if (!$response->successful()) {
                    $errors[] = "Gagal mengambil kurs {$code}: Status HTTP {$response->status()}";
                    continue;
                }

                $xmlContent = $response->body();
                $records = $this->parseXmlResponse($xmlContent);

                if (empty($records)) {
                    // Mungkin tidak ada pergerakan kurs pada interval tersebut (hari libur)
                    continue;
                }

                $currenciesProcessed++;

                foreach ($records as $item) {
                    $date = Carbon::parse($item['date'])->format('Y-m-d');
                    $unit = (float) ($item['unit'] ?? 1.0);
                    if ($unit <= 0) {
                        $unit = 1.0;
                    }
                    $buy = (float) ($item['buy'] ?? 0);
                    $sell = (float) ($item['sell'] ?? 0);
                    $middle = ($buy + $sell) / 2;

                    ExchangeRate::updateOrCreate(
                        [
                            'currency_code' => $code,
                            'date' => $date,
                        ],
                        [
                            'currency_id' => $currency->id,
                            'unit' => $unit,
                            'rate_buy' => $buy,
                            'rate_sell' => $sell,
                            'rate_middle' => $middle,
                            'source' => 'BI Kurs Transaksi',
                            'updated_by' => $userId,
                            'created_by' => $userId,
                        ]
                    );

                    $totalSynced++;
                }
            } catch (\Throwable $e) {
                Log::error("Error syncing exchange rates for {$code}: " . $e->getMessage());
                $errors[] = "Error {$code}: " . $e->getMessage();
            }
        }

        // Catat audit trail
        ActivityLogger::log(
            description: "Sinkronisasi kurs Bank Indonesia ({$totalSynced} kurs diproses dari {$currenciesProcessed} mata uang)",
            event: 'sync',
            logName: 'exchange_rates',
            properties: [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'synced_count' => $totalSynced,
                'currencies_processed' => $currenciesProcessed,
                'errors' => $errors,
            ],
            status: empty($errors) ? 'success' : 'warning'
        );

        return [
            'success' => true,
            'synced_count' => $totalSynced,
            'currencies_processed' => $currenciesProcessed,
            'errors' => $errors,
        ];
    }

    /**
     * Parsing XML DataSet dari Web Service Bank Indonesia.
     *
     * @return array<int, array{
     *     date: string,
     *     currency_code: string,
     *     unit: float,
     *     buy: float,
     *     sell: float
     * }>
     */
    protected function parseXmlResponse(string $xmlContent): array
    {
        $results = [];

        if (trim($xmlContent) === '') {
            return $results;
        }

        try {
            $dom = new DOMDocument();
            // Suppress warnings from potentially malformed XML
            libxml_use_internal_errors(true);
            $dom->loadXML($xmlContent);
            libxml_clear_errors();

            $tables = $dom->getElementsByTagName('Table');

            foreach ($tables as $table) {
                $fields = [];
                foreach ($table->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE) {
                        $fields[$child->nodeName] = trim($child->textContent);
                    }
                }

                if (isset($fields['tgl_subkurslokal'])) {
                    $results[] = [
                        'date' => $fields['tgl_subkurslokal'],
                        'currency_code' => trim($fields['mts_subkurslokal'] ?? ''),
                        'unit' => isset($fields['nil_subkurslokal']) ? (float) $fields['nil_subkurslokal'] : 1.0,
                        'buy' => isset($fields['beli_subkurslokal']) ? (float) $fields['beli_subkurslokal'] : 0.0,
                        'sell' => isset($fields['jual_subkurslokal']) ? (float) $fields['jual_subkurslokal'] : 0.0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('XML parsing error in BankIndonesiaRateService: ' . $e->getMessage());
        }

        return $results;
    }
}
