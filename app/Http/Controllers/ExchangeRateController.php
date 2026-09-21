<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\BankIndonesiaRateService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExchangeRateController extends Controller
{
    /**
     * Display a listing of exchange rates.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $date = $request->input('date');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $currencyCode = $request->input('currency');

        $query = ExchangeRate::query()
            ->with(['currency:id,code,name', 'creator:id,name', 'updater:id,name'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('currency_code', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhereHas('currency', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($date, function ($q, $date) {
                $q->whereDate('date', $date);
            })
            ->when($startDate && !$date, function ($q) use ($startDate) {
                $q->whereDate('date', '>=', $startDate);
            })
            ->when($endDate && !$date, function ($q) use ($endDate) {
                $q->whereDate('date', '<=', $endDate);
            })
            ->when($currencyCode, function ($q, $currencyCode) {
                $q->where('currency_code', strtoupper($currencyCode));
            });

        $rates = $query
            ->orderBy('date', 'desc')
            ->orderBy('currency_code', 'asc')
            ->paginate(15)
            ->withQueryString();

        $currencies = Currency::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Latest exchange rate date in DB
        $latestRecord = ExchangeRate::latest('date')->first();
        $latestDate = $latestRecord?->date ? Carbon::parse($latestRecord->date)->format('Y-m-d') : null;

        // Statistics
        $stats = [
            'total_records' => ExchangeRate::count(),
            'total_currencies' => $currencies->count(),
            'latest_date' => $latestDate,
            'rates_today_count' => ExchangeRate::whereDate('date', Carbon::today()->format('Y-m-d'))->count(),
            'selected_date_count' => $date ? ExchangeRate::whereDate('date', $date)->count() : null,
        ];

        return Inertia::render('ExchangeRates/Index', [
            'rates' => $rates,
            'currencies' => $currencies,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'date' => $date,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'currency' => $currencyCode,
            ],
        ]);
    }

    /**
     * Store a newly created exchange rate.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'unit' => ['required', 'numeric', 'min:0.01'],
            'rate_buy' => ['required', 'numeric', 'min:0'],
            'rate_sell' => ['required', 'numeric', 'min:0'],
            'rate_middle' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:50'],
        ], [
            'currency_id.required' => 'Mata uang wajib dipilih.',
            'currency_id.exists' => 'Mata uang tidak valid.',
            'date.required' => 'Tanggal kurs wajib diisi.',
            'date.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'unit.required' => 'Satuan nominal unit wajib diisi.',
            'rate_buy.required' => 'Nilai kurs beli wajib diisi.',
            'rate_sell.required' => 'Nilai kurs jual wajib diisi.',
        ]);

        $currency = Currency::findOrFail($validated['currency_id']);

        // Cek duplikasi kurs untuk mata uang dan tanggal yang sama
        $exists = ExchangeRate::where('currency_code', $currency->code)
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['date' => "Kurs untuk mata uang {$currency->code} pada tanggal {$validated['date']} sudah terdaftar."])
                ->withInput();
        }

        $rateBuy = (float) $validated['rate_buy'];
        $rateSell = (float) $validated['rate_sell'];
        $rateMiddle = !empty($validated['rate_middle'])
            ? (float) $validated['rate_middle']
            : ($rateBuy + $rateSell) / 2;

        ExchangeRate::create([
            'currency_id' => $currency->id,
            'currency_code' => $currency->code,
            'date' => $validated['date'],
            'unit' => (float) $validated['unit'],
            'rate_buy' => $rateBuy,
            'rate_sell' => $rateSell,
            'rate_middle' => $rateMiddle,
            'source' => ($validated['source'] ?? null) ?: 'Manual',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        Cache::forget('navbar_exchange_rates');

        return redirect()->route('exchange-rates.index')->with('success', 'Data kurs berhasil ditambahkan.');
    }

    /**
     * Update the specified exchange rate.
     */
    public function update(Request $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $validated = $request->validate([
            'unit' => ['required', 'numeric', 'min:0.01'],
            'rate_buy' => ['required', 'numeric', 'min:0'],
            'rate_sell' => ['required', 'numeric', 'min:0'],
            'rate_middle' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:50'],
        ], [
            'unit.required' => 'Satuan nominal unit wajib diisi.',
            'rate_buy.required' => 'Nilai kurs beli wajib diisi.',
            'rate_sell.required' => 'Nilai kurs jual wajib diisi.',
        ]);

        $rateBuy = (float) $validated['rate_buy'];
        $rateSell = (float) $validated['rate_sell'];
        $rateMiddle = !empty($validated['rate_middle'])
            ? (float) $validated['rate_middle']
            : ($rateBuy + $rateSell) / 2;

        $exchangeRate->update([
            'unit' => (float) $validated['unit'],
            'rate_buy' => $rateBuy,
            'rate_sell' => $rateSell,
            'rate_middle' => $rateMiddle,
            'source' => $validated['source'] ?? $exchangeRate->source,
            'updated_by' => Auth::id(),
        ]);

        Cache::forget('navbar_exchange_rates');

        return redirect()->back()->with('success', 'Data kurs berhasil diperbarui.');
    }

    /**
     * Remove the specified exchange rate.
     */
    public function destroy(ExchangeRate $exchangeRate): RedirectResponse
    {
        $exchangeRate->delete();

        Cache::forget('navbar_exchange_rates');

        return redirect()->back()->with('success', 'Data kurs berhasil dipindahkan ke tempat sampah.');
    }

    /**
     * Sinkronisasi data kurs dari Bank Indonesia melalui UI.
     */
    public function sync(Request $request, BankIndonesiaRateService $service): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'currency_codes' => ['nullable', 'array'],
            'currency_codes.*' => ['string', 'max:10'],
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = $validated['end_date'] ?? Carbon::now()->format('Y-m-d');
        $currencyCodes = $validated['currency_codes'] ?? null;

        $result = $service->syncRates(
            currencyCodes: $currencyCodes,
            startDate: $startDate,
            endDate: $endDate,
            userId: Auth::id()
        );

        Cache::forget('navbar_exchange_rates');

        if ($result['success']) {
            $msg = "Sinkronisasi Bank Indonesia berhasil. Diperbarui {$result['synced_count']} data kurs dari {$result['currencies_processed']} mata uang.";
            if (!empty($result['errors'])) {
                $msg .= " Catatan: beberapa mata uang tidak ada kurs baru.";
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'data' => $result,
                ]);
            }

            return redirect()->back()->with('success', $msg);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyinkronkan data kurs Bank Indonesia.',
            ], 500);
        }

        return redirect()->back()->with('error', 'Gagal menyinkronkan data kurs Bank Indonesia.');
    }
}
