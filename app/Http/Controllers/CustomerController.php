<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerBank;
use App\Models\TermOfPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $termPaymentId = $request->input('term_of_payment_id');
        $currencyId = $request->input('currency_id');

        $customers = Customer::query()
            ->with([
                'termOfPayment:id,payment,name,days',
                'currency:id,code,name',
                'receivableAccount:id,account_code,account_name',
                'banks',
                'creator:id,name',
                'updater:id,name',
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('alias', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('contact_person_phone', 'like', "%{$search}%")
                        ->orWhere('contact_person_email', 'like', "%{$search}%")
                        ->orWhereHas('banks', function ($bq) use ($search) {
                            $bq->where('bank_name', 'like', "%{$search}%")
                                ->orWhere('bank_account_number', 'like', "%{$search}%")
                                ->orWhere('bank_account_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $isActive = filter_var($status, FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $isActive);
            })
            ->when($termPaymentId !== null && $termPaymentId !== '', function ($query) use ($termPaymentId) {
                $query->where('term_of_payment_id', $termPaymentId);
            })
            ->when($currencyId !== null && $currencyId !== '', function ($query) use ($currencyId) {
                $query->where('currency_id', $currencyId);
            })
            ->orderBy('customer_code', 'asc')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('is_active', true)->count(),
            'inactive' => Customer::where('is_active', false)->count(),
            'with_receivable_coa' => Customer::whereNotNull('coa_receivable_id')->count(),
        ];

        $termOfPayments = TermOfPayment::query()
            ->where('is_active', true)
            ->orderBy('days', 'asc')
            ->orderBy('payment', 'asc')
            ->get(['id', 'payment', 'name', 'days']);

        $currencies = Currency::query()
            ->orderBy('code', 'asc')
            ->get(['id', 'code', 'name']);

        $chartOfAccounts = ChartOfAccount::query()
            ->where('postable', true)
            ->where('is_active', true)
            ->orderBy('account_code', 'asc')
            ->get(['id', 'account_code', 'account_name', 'jenis']);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'stats' => $stats,
            'termOfPayments' => $termOfPayments,
            'currencies' => $currencies,
            'chartOfAccounts' => $chartOfAccounts,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'term_of_payment_id' => $termPaymentId,
                'currency_id' => $currencyId,
            ],
            'suggestedCode' => $this->generateNextCode(),
        ]);
    }

    /**
     * Generate the next sequential customer code (e.g. CUST-0001).
     */
    public function generateCode(): JsonResponse
    {
        return response()->json([
            'code' => $this->generateNextCode(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->has('customer_code') && is_string($request->input('customer_code'))) {
            $request->merge(['customer_code' => strtoupper(trim($request->input('customer_code')))]);
        }

        $validated = $request->validate([
            'customer_code' => ['required', 'string', 'max:50', 'unique:customers,customer_code'],
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'term_of_payment_id' => ['nullable', 'uuid', 'exists:term_of_payments,id'],
            'currency_id' => ['nullable', 'uuid', 'exists:currencies,id'],
            'coa_receivable_id' => ['nullable', 'uuid', 'exists:chart_of_accounts,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_person_phone' => ['nullable', 'string', 'max:50'],
            'contact_person_email' => ['nullable', 'email', 'max:100'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'tax_address' => ['nullable', 'string', 'max:1000'],
            'delivery_lead_time_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Bank Account attributes (Primary Bank)
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_branch' => ['nullable', 'string', 'max:100'],
            'swift_code' => ['nullable', 'string', 'max:50'],
        ], [
            'customer_code.required' => 'Kode customer wajib diisi.',
            'customer_code.unique' => 'Kode customer ini sudah digunakan.',
            'name.required' => 'Nama customer wajib diisi.',
            'email.email' => 'Format email kantor tidak valid.',
            'contact_person_email.email' => 'Format email PIC tidak valid.',
            'term_of_payment_id.exists' => 'Term of Payment yang dipilih tidak valid.',
            'currency_id.exists' => 'Mata uang yang dipilih tidak valid.',
            'coa_receivable_id.exists' => 'Akun piutang yang dipilih tidak valid.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $customer = Customer::create([
                'customer_code' => $validated['customer_code'],
                'name' => trim($validated['name']),
                'alias' => !empty($validated['alias']) ? trim($validated['alias']) : null,
                'tax_id' => $validated['tax_id'] ?? null,
                'term_of_payment_id' => $validated['term_of_payment_id'] ?? null,
                'currency_id' => $validated['currency_id'] ?? null,
                'coa_receivable_id' => $validated['coa_receivable_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'fax' => $validated['fax'] ?? null,
                'email' => $validated['email'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'contact_person_phone' => $validated['contact_person_phone'] ?? null,
                'contact_person_email' => $validated['contact_person_email'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'tax_address' => $validated['tax_address'] ?? null,
                'delivery_lead_time_days' => $validated['delivery_lead_time_days'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Save primary bank account if details provided
            if (!empty($validated['bank_account_number']) && !empty($validated['bank_name'])) {
                CustomerBank::create([
                    'customer_id' => $customer->id,
                    'bank_name' => trim($validated['bank_name']),
                    'bank_account_number' => trim($validated['bank_account_number']),
                    'bank_account_name' => !empty($validated['bank_account_name']) ? trim($validated['bank_account_name']) : $customer->name,
                    'bank_branch' => $validated['bank_branch'] ?? null,
                    'swift_code' => $validated['swift_code'] ?? null,
                    'is_primary' => true,
                ]);
            }
        });

        return redirect()->route('customers.index')->with('success', 'Data customer baru berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        if ($request->has('customer_code') && is_string($request->input('customer_code'))) {
            $request->merge(['customer_code' => strtoupper(trim($request->input('customer_code')))]);
        }

        $validated = $request->validate([
            'customer_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'customer_code')->ignore($customer->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:100'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'term_of_payment_id' => ['nullable', 'uuid', 'exists:term_of_payments,id'],
            'currency_id' => ['nullable', 'uuid', 'exists:currencies,id'],
            'coa_receivable_id' => ['nullable', 'uuid', 'exists:chart_of_accounts,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_person_phone' => ['nullable', 'string', 'max:50'],
            'contact_person_email' => ['nullable', 'email', 'max:100'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'tax_address' => ['nullable', 'string', 'max:1000'],
            'delivery_lead_time_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Bank Account attributes (Primary Bank)
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_branch' => ['nullable', 'string', 'max:100'],
            'swift_code' => ['nullable', 'string', 'max:50'],
        ], [
            'customer_code.required' => 'Kode customer wajib diisi.',
            'customer_code.unique' => 'Kode customer ini sudah digunakan.',
            'name.required' => 'Nama customer wajib diisi.',
            'email.email' => 'Format email kantor tidak valid.',
            'contact_person_email.email' => 'Format email PIC tidak valid.',
            'term_of_payment_id.exists' => 'Term of Payment yang dipilih tidak valid.',
            'currency_id.exists' => 'Mata uang yang dipilih tidak valid.',
            'coa_receivable_id.exists' => 'Akun piutang yang dipilih tidak valid.',
        ]);

        DB::transaction(function () use ($customer, $validated, $request) {
            $customer->update([
                'customer_code' => $validated['customer_code'],
                'name' => trim($validated['name']),
                'alias' => !empty($validated['alias']) ? trim($validated['alias']) : null,
                'tax_id' => $validated['tax_id'] ?? null,
                'term_of_payment_id' => $validated['term_of_payment_id'] ?? null,
                'currency_id' => $validated['currency_id'] ?? null,
                'coa_receivable_id' => $validated['coa_receivable_id'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'fax' => $validated['fax'] ?? null,
                'email' => $validated['email'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'contact_person_phone' => $validated['contact_person_phone'] ?? null,
                'contact_person_email' => $validated['contact_person_email'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'tax_address' => $validated['tax_address'] ?? null,
                'delivery_lead_time_days' => $validated['delivery_lead_time_days'] ?? 0,
                'is_active' => $request->boolean('is_active', true),
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            // Sync or update primary bank account
            if (!empty($validated['bank_account_number']) && !empty($validated['bank_name'])) {
                $primaryBank = $customer->banks()->where('is_primary', true)->first();
                if ($primaryBank) {
                    $primaryBank->update([
                        'bank_name' => trim($validated['bank_name']),
                        'bank_account_number' => trim($validated['bank_account_number']),
                        'bank_account_name' => !empty($validated['bank_account_name']) ? trim($validated['bank_account_name']) : $customer->name,
                        'bank_branch' => $validated['bank_branch'] ?? null,
                        'swift_code' => $validated['swift_code'] ?? null,
                    ]);
                } else {
                    CustomerBank::create([
                        'customer_id' => $customer->id,
                        'bank_name' => trim($validated['bank_name']),
                        'bank_account_number' => trim($validated['bank_account_number']),
                        'bank_account_name' => !empty($validated['bank_account_name']) ? trim($validated['bank_account_name']) : $customer->name,
                        'bank_branch' => $validated['bank_branch'] ?? null,
                        'swift_code' => $validated['swift_code'] ?? null,
                        'is_primary' => true,
                    ]);
                }
            }
        });

        return redirect()->route('customers.index')->with('success', 'Data customer berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Data customer berhasil dipindahkan ke tempat sampah.');
    }

    /**
     * Helper to compute next code: e.g. CUST-0001, CUST-0002.
     */
    protected function generateNextCode(): string
    {
        $prefix = 'CUST-';

        $latest = Customer::withTrashed()
            ->where('customer_code', 'like', "{$prefix}%")
            ->orderBy('customer_code', 'desc')
            ->value('customer_code');

        if (!$latest) {
            return $prefix . '0001';
        }

        if (preg_match('/(\d+)$/', $latest, $matches)) {
            $nextNum = (int) $matches[1] + 1;
            return $prefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '0001';
    }
}
