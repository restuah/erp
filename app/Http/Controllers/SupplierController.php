<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Supplier;
use App\Models\TermOfPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');
        $termPaymentId = $request->input('term_of_payment_id');

        $suppliers = Supplier::query()
            ->with([
                'termOfPayment:id,payment,name,days',
                'creator:id,name',
                'updater:id,name',
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_code', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%")
                        ->orWhere('marketing_name', 'like', "%{$search}%")
                        ->orWhere('marketing_phone', 'like', "%{$search}%")
                        ->orWhere('marketing_email', 'like', "%{$search}%")
                        ->orWhere('accounting_name', 'like', "%{$search}%")
                        ->orWhere('accounting_phone', 'like', "%{$search}%")
                        ->orWhere('accounting_email', 'like', "%{$search}%")
                        ->orWhere('npwp', 'like', "%{$search}%")
                        ->orWhere('office_phone', 'like', "%{$search}%")
                        ->orWhere('bank_account_number', 'like', "%{$search}%")
                        ->orWhereHas('termOfPayment', function ($tq) use ($search) {
                            $tq->where('payment', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($type !== null && $type !== '', function ($query) use ($type) {
                $query->where('supplier_type', $type);
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($termPaymentId !== null && $termPaymentId !== '', function ($query) use ($termPaymentId) {
                $query->where('term_of_payment_id', $termPaymentId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Supplier::count(),
            'direct' => Supplier::where('supplier_type', 'direct')->count(),
            'indirect' => Supplier::where('supplier_type', 'indirect')->count(),
            'active' => Supplier::where('status', 'active')->count(),
            'nonactive' => Supplier::where('status', 'nonactive')->count(),
        ];

        $termOfPayments = TermOfPayment::query()
            ->where('is_active', true)
            ->orderBy('days', 'asc')
            ->orderBy('payment', 'asc')
            ->get(['id', 'payment', 'name', 'days']);

        $currencies = Currency::query()
            ->orderBy('code', 'asc')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'stats' => $stats,
            'termOfPayments' => $termOfPayments,
            'currencies' => $currencies,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'term_of_payment_id' => $termPaymentId,
            ],
            'suggestedCodes' => [
                'direct' => $this->generateNextCode('direct'),
                'indirect' => $this->generateNextCode('indirect'),
            ],
        ]);
    }

    /**
     * Generate the next sequential supplier code for direct or indirect type.
     */
    public function generateCode(Request $request): JsonResponse
    {
        $type = $request->input('type', 'direct');
        if (!in_array($type, ['direct', 'indirect'], true)) {
            $type = 'direct';
        }

        return response()->json([
            'code' => $this->generateNextCode($type),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->has('supplier_code') && is_string($request->input('supplier_code'))) {
            $request->merge(['supplier_code' => strtoupper(trim($request->input('supplier_code')))]);
        }
        if ($request->has('default_currency') && is_string($request->input('default_currency'))) {
            $request->merge(['default_currency' => strtoupper(trim($request->input('default_currency')))]);
        }
        if ($request->has('country_code') && is_string($request->input('country_code'))) {
            $request->merge(['country_code' => strtoupper(trim($request->input('country_code')))]);
        }

        $validated = $request->validate([
            'supplier_type' => ['required', Rule::in(['direct', 'indirect'])],
            'supplier_code' => ['required', 'string', 'max:50', 'unique:suppliers,supplier_code'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'nonactive'])],
            'term_of_payment_id' => ['nullable', 'uuid', 'exists:term_of_payments,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'zipcode' => ['nullable', 'string', 'max:20'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'office_phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'marketing_name' => ['nullable', 'string', 'max:100'],
            'marketing_phone' => ['nullable', 'string', 'max:50'],
            'marketing_email' => ['nullable', 'email', 'max:100'],
            'accounting_name' => ['nullable', 'string', 'max:100'],
            'accounting_phone' => ['nullable', 'string', 'max:50'],
            'accounting_email' => ['nullable', 'email', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'default_currency' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'delivery_period' => ['nullable', 'string', 'max:100'],
            'delivery_time' => ['nullable', 'string', 'max:20'],
            'delivery_end_time' => ['nullable', 'string', 'max:20'],
        ], [
            'supplier_type.required' => 'Tipe supplier wajib dipilih.',
            'supplier_type.in' => 'Tipe supplier harus Direct atau Indirect.',
            'supplier_code.required' => 'Kode supplier wajib diisi.',
            'supplier_code.unique' => 'Kode supplier ini sudah digunakan.',
            'supplier_name.required' => 'Nama supplier wajib diisi.',
            'status.required' => 'Status supplier wajib dipilih.',
            'marketing_email.email' => 'Format email marketing tidak valid.',
            'accounting_email.email' => 'Format email accounting tidak valid.',
            'term_of_payment_id.exists' => 'Term of Payment yang dipilih tidak valid.',
        ]);

        Supplier::create([
            ...$validated,
            'supplier_name' => trim($validated['supplier_name']),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('suppliers.index')->with('success', 'Data supplier baru berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        if ($request->has('supplier_code') && is_string($request->input('supplier_code'))) {
            $request->merge(['supplier_code' => strtoupper(trim($request->input('supplier_code')))]);
        }
        if ($request->has('default_currency') && is_string($request->input('default_currency'))) {
            $request->merge(['default_currency' => strtoupper(trim($request->input('default_currency')))]);
        }
        if ($request->has('country_code') && is_string($request->input('country_code'))) {
            $request->merge(['country_code' => strtoupper(trim($request->input('country_code')))]);
        }

        $validated = $request->validate([
            'supplier_type' => ['required', Rule::in(['direct', 'indirect'])],
            'supplier_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('suppliers', 'supplier_code')->ignore($supplier->id),
            ],
            'supplier_name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'nonactive'])],
            'term_of_payment_id' => ['nullable', 'uuid', 'exists:term_of_payments,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'zipcode' => ['nullable', 'string', 'max:20'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'office_phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'marketing_name' => ['nullable', 'string', 'max:100'],
            'marketing_phone' => ['nullable', 'string', 'max:50'],
            'marketing_email' => ['nullable', 'email', 'max:100'],
            'accounting_name' => ['nullable', 'string', 'max:100'],
            'accounting_phone' => ['nullable', 'string', 'max:50'],
            'accounting_email' => ['nullable', 'email', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'default_currency' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'delivery_period' => ['nullable', 'string', 'max:100'],
            'delivery_time' => ['nullable', 'string', 'max:20'],
            'delivery_end_time' => ['nullable', 'string', 'max:20'],
        ], [
            'supplier_type.required' => 'Tipe supplier wajib dipilih.',
            'supplier_type.in' => 'Tipe supplier harus Direct atau Indirect.',
            'supplier_code.required' => 'Kode supplier wajib diisi.',
            'supplier_code.unique' => 'Kode supplier ini sudah digunakan.',
            'supplier_name.required' => 'Nama supplier wajib diisi.',
            'status.required' => 'Status supplier wajib dipilih.',
            'marketing_email.email' => 'Format email marketing tidak valid.',
            'accounting_email.email' => 'Format email accounting tidak valid.',
            'term_of_payment_id.exists' => 'Term of Payment yang dipilih tidak valid.',
        ]);

        $supplier->update([
            ...$validated,
            'supplier_name' => trim($validated['supplier_name']),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('suppliers.index')->with('success', 'Data supplier berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Data supplier berhasil dipindahkan ke tempat sampah.');
    }

    /**
     * Helper to compute next code: e.g. SUP-DIR-0001 or SUP-IND-0001.
     */
    protected function generateNextCode(string $type = 'direct'): string
    {
        $prefix = ($type === 'indirect') ? 'SUP-IND-' : 'SUP-DIR-';

        // Find highest sequential number with this prefix
        $latest = Supplier::withTrashed()
            ->where('supplier_code', 'like', "{$prefix}%")
            ->orderBy('supplier_code', 'desc')
            ->value('supplier_code');

        if (!$latest) {
            return $prefix . '0001';
        }

        // Extract trailing digits
        if (preg_match('/(\d+)$/', $latest, $matches)) {
            $nextNum = (int) $matches[1] + 1;
            return $prefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '0001';
    }
}
