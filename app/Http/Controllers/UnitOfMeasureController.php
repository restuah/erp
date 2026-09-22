<?php

namespace App\Http\Controllers;

use App\Models\UnitOfMeasure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UnitOfMeasureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $status = $request->input('status');

        $units = UnitOfMeasure::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category !== null && $category !== '', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('category')
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => UnitOfMeasure::count(),
            'active' => UnitOfMeasure::where('is_active', true)->count(),
            'inactive' => UnitOfMeasure::where('is_active', false)->count(),
            'categories_count' => UnitOfMeasure::distinct('category')->count('category'),
        ];

        return Inertia::render('UnitOfMeasures/Index', [
            'units' => $units,
            'stats' => $stats,
            'categories' => UnitOfMeasure::categories(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->has('code') && is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:unit_of_measures,code'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:20'],
            'category' => ['required', 'string', Rule::in(array_keys(UnitOfMeasure::categories()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'code.required' => 'Kode satuan wajib diisi.',
            'code.unique' => 'Kode satuan ini sudah digunakan.',
            'name.required' => 'Nama satuan wajib diisi.',
            'category.required' => 'Kategori satuan wajib dipilih.',
            'category.in' => 'Kategori satuan tidak valid.',
        ]);

        UnitOfMeasure::create([
            'code' => $validated['code'],
            'name' => trim($validated['name']),
            'symbol' => isset($validated['symbol']) ? trim($validated['symbol']) : null,
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('unit-of-measures.index')->with('success', 'Satuan (UOM) baru berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UnitOfMeasure $unitOfMeasure): RedirectResponse
    {
        if ($request->has('code') && is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('unit_of_measures', 'code')->ignore($unitOfMeasure->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:20'],
            'category' => ['required', 'string', Rule::in(array_keys(UnitOfMeasure::categories()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'code.required' => 'Kode satuan wajib diisi.',
            'code.unique' => 'Kode satuan ini sudah digunakan.',
            'name.required' => 'Nama satuan wajib diisi.',
            'category.required' => 'Kategori satuan wajib dipilih.',
            'category.in' => 'Kategori satuan tidak valid.',
        ]);

        $unitOfMeasure->update([
            'code' => $validated['code'],
            'name' => trim($validated['name']),
            'symbol' => isset($validated['symbol']) ? trim($validated['symbol']) : null,
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('unit-of-measures.index')->with('success', 'Satuan (UOM) berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UnitOfMeasure $unitOfMeasure): RedirectResponse
    {
        $unitOfMeasure->delete();

        return redirect()->route('unit-of-measures.index')->with('success', 'Satuan (UOM) berhasil dipindahkan ke tempat sampah.');
    }
}
