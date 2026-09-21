<?php

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'avatar_url' => $user->avatar_url,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ]) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'navbar_exchange_rates' => function () use ($user) {
                if (!$user) {
                    return [];
                }

                return Cache::remember('navbar_exchange_rates', 60, function () {
                    $currencies = Currency::where('code', '!=', 'IDR')
                        ->orderBy('code')
                        ->get(['id', 'code', 'name']);

                    $latestRates = ExchangeRate::select('exchange_rates.*')
                        ->join(
                            DB::raw('(SELECT currency_code, MAX(date) as max_date FROM exchange_rates WHERE deleted_at IS NULL GROUP BY currency_code) as latest'),
                            function ($join) {
                                $join->on('exchange_rates.currency_code', '=', 'latest.currency_code')
                                     ->on('exchange_rates.date', '=', 'latest.max_date');
                            }
                        )
                        ->whereNull('exchange_rates.deleted_at')
                        ->get()
                        ->keyBy('currency_code');

                    return $currencies->map(function ($c) use ($latestRates) {
                        $rate = $latestRates->get($c->code);
                        return [
                            'currency_id' => $c->id,
                            'currency_code' => $c->code,
                            'currency_name' => $c->name,
                            'unit' => $rate ? (float) $rate->unit : 1.0,
                            'rate_middle' => $rate ? (float) $rate->rate_middle : null,
                            'date' => $rate?->date ? Carbon::parse($rate->date)->format('Y-m-d') : null,
                            'updated_at' => $rate?->updated_at ? $rate->updated_at->toISOString() : null,
                            'formatted_updated_at' => $rate?->updated_at ? $rate->updated_at->timezone(config('app.timezone', 'Asia/Jakarta'))->translatedFormat('d M Y, H:i') : null,
                        ];
                    })->values();
                });
            },
        ];
    }
}
