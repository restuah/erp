<?php

namespace App\Http\Controllers;

use App\Models\BudgetClassification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetClassificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $classifications = BudgetClassification::query()
            ->withCount('budgets')
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => BudgetClassification::count(),
            'active' => BudgetClassification::where('is_active', true)->count(),
            'inactive' => BudgetClassification::where('is_active', false)->count(),
        ];

        return Inertia::render('BudgetClassifications/Index', [
            'classifications' => $classifications,
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:budget_classifications,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Nama klasifikasi budget wajib diisi.',
            'name.unique' => 'Nama klasifikasi budget ini sudah digunakan.',
        ]);

        BudgetClassification::create([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('budget-classifications.index')->with('success', 'Klasifikasi Budget berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BudgetClassification $budgetClassification): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('budget_classifications', 'name')->ignore($budgetClassification->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Nama klasifikasi budget wajib diisi.',
            'name.unique' => 'Nama klasifikasi budget ini sudah digunakan.',
        ]);

        $budgetClassification->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('budget-classifications.index')->with('success', 'Klasifikasi Budget berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BudgetClassification $budgetClassification): RedirectResponse
    {
        $budgetClassification->delete();

        return redirect()->route('budget-classifications.index')->with('success', 'Klasifikasi Budget berhasil dihapus.');
    }
}
