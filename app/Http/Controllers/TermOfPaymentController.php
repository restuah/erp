<?php

namespace App\Http\Controllers;

use App\Models\TermOfPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TermOfPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $terms = TermOfPayment::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('days', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('days', 'asc')
            ->orderBy('payment', 'asc')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => TermOfPayment::count(),
            'active' => TermOfPayment::where('is_active', true)->count(),
            'inactive' => TermOfPayment::where('is_active', false)->count(),
            'average_days' => (int) round(TermOfPayment::avg('days') ?? 0),
        ];

        return Inertia::render('TermOfPayments/Index', [
            'terms' => $terms,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->has('payment') && is_string($request->input('payment'))) {
            $request->merge(['payment' => strtoupper(trim($request->input('payment')))]);
        }

        $validated = $request->validate([
            'payment' => ['required', 'string', 'max:50', 'unique:term_of_payments,payment'],
            'name' => ['required', 'string', 'max:150'],
            'days' => ['required', 'integer', 'min:0', 'max:9999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'payment.required' => 'Kode termin (payment) wajib diisi.',
            'payment.unique' => 'Kode termin ini sudah terdaftar.',
            'name.required' => 'Nama termin wajib diisi.',
            'days.required' => 'Jumlah hari jatuh tempo wajib diisi.',
            'days.integer' => 'Jumlah hari harus berupa angka bulat.',
            'days.min' => 'Jumlah hari minimal 0.',
        ]);

        TermOfPayment::create([
            'payment' => $validated['payment'],
            'name' => trim($validated['name']),
            'days' => (int) $validated['days'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('term-of-payments.index')->with('success', 'Term of Payment (TOP) berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TermOfPayment $termOfPayment): RedirectResponse
    {
        if ($request->has('payment') && is_string($request->input('payment'))) {
            $request->merge(['payment' => strtoupper(trim($request->input('payment')))]);
        }

        $validated = $request->validate([
            'payment' => [
                'required',
                'string',
                'max:50',
                Rule::unique('term_of_payments', 'payment')->ignore($termOfPayment->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'days' => ['required', 'integer', 'min:0', 'max:9999'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'payment.required' => 'Kode termin (payment) wajib diisi.',
            'payment.unique' => 'Kode termin ini sudah terdaftar.',
            'name.required' => 'Nama termin wajib diisi.',
            'days.required' => 'Jumlah hari jatuh tempo wajib diisi.',
            'days.integer' => 'Jumlah hari harus berupa angka bulat.',
            'days.min' => 'Jumlah hari minimal 0.',
        ]);

        $termOfPayment->update([
            'payment' => $validated['payment'],
            'name' => trim($validated['name']),
            'days' => (int) $validated['days'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('term-of-payments.index')->with('success', 'Term of Payment (TOP) berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TermOfPayment $termOfPayment): RedirectResponse
    {
        // Check if referenced by any suppliers
        if ($termOfPayment->suppliers()->exists()) {
            return redirect()->back()->with('error', 'Term of Payment tidak dapat dihapus karena sedang digunakan oleh data supplier.');
        }

        $termOfPayment->delete();

        return redirect()->route('term-of-payments.index')->with('success', 'Term of Payment berhasil dipindahkan ke tempat sampah.');
    }
}
