<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Budget;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Supplier;
use App\Models\TermOfPayment;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the enterprise dashboard overview.
     */
    public function index(Request $request): Response
    {
        $supplierTotal = Supplier::count();
        $supplierActive = Supplier::where('status', 'active')->count();
        $supplierDirect = Supplier::where('supplier_type', 'direct')->count();
        $supplierIndirect = Supplier::where('supplier_type', 'indirect')->count();

        $budgetTotal = Budget::count();
        $budgetActive = Budget::where('is_active', true)->count();

        $currenciesCount = Currency::count();
        $usersCount = User::count();
        $usersWith2fa = User::whereNotNull('two_factor_confirmed_at')->count();
        $topCount = TermOfPayment::count();

        // Latest exchange rates for major currencies
        $latestRates = ExchangeRate::with('currency')
            ->whereIn('currency_code', ['USD', 'EUR', 'SGD', 'JPY', 'CNY', 'GBP', 'AUD', 'MYR'])
            ->orderBy('date', 'desc')
            ->get()
            ->unique('currency_code')
            ->values()
            ->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'code' => $rate->currency_code,
                    'name' => $rate->currency?->name ?? $rate->currency_code,
                    'rate_middle' => (float) $rate->rate_middle,
                    'rate_buy' => (float) $rate->rate_buy,
                    'rate_sell' => (float) $rate->rate_sell,
                    'date' => $rate->date,
                    'source' => $rate->source,
                ];
            });

        // Recent Audit / Activity Logs
        $recentActivities = ActivityLog::latest()
            ->take(6)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->description,
                    'causer_name' => $log->causer_name ?? 'Sistem',
                    'causer_role' => $log->causer_role ?? 'System',
                    'status' => $log->status,
                    'device' => $log->device,
                    'created_at' => $log->created_at?->diffForHumans(),
                    'created_at_formatted' => $log->created_at?->format('d M Y, H:i'),
                ];
            });

        // Top recent suppliers
        $recentSuppliers = Supplier::with('termOfPayment')
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($s) {
                $addressParts = array_filter(array_map('trim', explode(',', $s->address ?? '')));
                $city = ! empty($addressParts) ? end($addressParts) : 'Indonesia';

                return [
                    'id' => $s->id,
                    'code' => $s->supplier_code,
                    'name' => $s->supplier_name,
                    'type' => $s->supplier_type,
                    'status' => $s->status,
                    'top_name' => $s->termOfPayment?->name ?? 'N/A',
                    'city' => $city,
                ];
            });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'suppliers' => [
                    'total' => $supplierTotal,
                    'active' => $supplierActive,
                    'direct' => $supplierDirect,
                    'indirect' => $supplierIndirect,
                ],
                'budgets' => [
                    'total' => $budgetTotal,
                    'active' => $budgetActive,
                ],
                'currencies' => [
                    'total' => $currenciesCount,
                ],
                'users' => [
                    'total' => $usersCount,
                    'with_2fa' => $usersWith2fa,
                ],
                'tops' => [
                    'total' => $topCount,
                ],
            ],
            'latest_rates' => $latestRates,
            'recent_activities' => $recentActivities,
            'recent_suppliers' => $recentSuppliers,
            'system_info' => [
                'company_name' => 'PT. Mandiri Jaya Utama',
                'branch' => 'Kantor Pusat (HQ)',
                'environment' => config('app.env', 'production'),
                'timezone' => 'Asia/Jakarta (WIB)',
                'active_period' => 'Q3 2026 • Tahun Fiskal 2026',
                'laravel_version' => Application::VERSION,
                'php_version' => PHP_VERSION,
            ],
        ]);
    }
}
