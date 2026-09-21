<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $currencies = Currency::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Currency::count(),
        ];

        return Inertia::render('Currencies/Index', [
            'currencies' => $currencies,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9]+$/',
                'unique:currencies,code',
            ],
            'name' => ['required', 'string', 'max:100'],
        ], [
            'code.required' => 'Kode mata uang wajib diisi.',
            'code.unique' => 'Kode mata uang ini sudah terdaftar.',
            'code.regex' => 'Kode mata uang hanya boleh berisi huruf dan angka tanpa spasi.',
            'name.required' => 'Nama mata uang wajib diisi.',
        ]);

        Currency::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        Cache::forget('navbar_exchange_rates');

        return redirect()->route('currencies.index')->with('success', 'Mata uang berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('currencies', 'code')->ignore($currency->id),
            ],
            'name' => ['required', 'string', 'max:100'],
        ], [
            'code.required' => 'Kode mata uang wajib diisi.',
            'code.unique' => 'Kode mata uang ini sudah digunakan oleh data lain.',
            'code.regex' => 'Kode mata uang hanya boleh berisi huruf dan angka tanpa spasi.',
            'name.required' => 'Nama mata uang wajib diisi.',
        ]);

        $currency->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'updated_by' => Auth::id(),
        ]);

        Cache::forget('navbar_exchange_rates');

        return redirect()->route('currencies.index')->with('success', 'Mata uang berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        $currency->delete();

        Cache::forget('navbar_exchange_rates');

        return redirect()->route('currencies.index')->with('success', 'Mata uang berhasil dipindahkan ke tempat sampah.');
    }
}
