<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $departments = Department::query()
            ->with([
                'manager:id,name,email',
                'creator:id,name',
                'updater:id,name',
            ])
            ->withCount(['users', 'budgets'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('manager', function ($mq) use ($search) {
                            $mq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name', 'asc')
            ->paginate(10)
            ->withQueryString();

        $users = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Department::count(),
            'active' => Department::where('is_active', true)->count(),
            'inactive' => Department::where('is_active', false)->count(),
            'with_manager' => Department::whereNotNull('manager_id')->count(),
        ];

        return Inertia::render('Departments/Index', [
            'departments' => $departments,
            'users' => $users,
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
        if ($request->has('code') && is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:150'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'code.required' => 'Kode departemen wajib diisi.',
            'code.unique' => 'Kode departemen ini sudah digunakan.',
            'name.required' => 'Nama departemen wajib diisi.',
            'manager_id.exists' => 'Manager yang dipilih tidak valid.',
        ]);

        Department::create([
            'code' => $validated['code'],
            'name' => trim($validated['name']),
            'manager_id' => $validated['manager_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('departments.index')->with('success', 'Departemen baru berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        if ($request->has('code') && is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->ignore($department->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'code.required' => 'Kode departemen wajib diisi.',
            'code.unique' => 'Kode departemen ini sudah digunakan.',
            'name.required' => 'Nama departemen wajib diisi.',
            'manager_id.exists' => 'Manager yang dipilih tidak valid.',
        ]);

        $department->update([
            'code' => $validated['code'],
            'name' => trim($validated['name']),
            'manager_id' => $validated['manager_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil diperbarui.');
    }

    /**
     * Display the specified resource details asynchronously (users and budgets).
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'users' => $department->users()->select('id', 'name', 'email', 'department_id')->orderBy('name')->get(),
            'budgets' => $department->budgets()->select('id', 'code', 'name', 'department_id')->orderBy('code')->get(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department): RedirectResponse
    {
        if ($department->users()->exists() || $department->budgets()->exists()) {
            return redirect()->route('departments.index')->with(
                'error',
                'Departemen tidak dapat dihapus karena masih memiliki anggota karyawan atau pos anggaran aktif. Harap alihkan relasi tersebut terlebih dahulu.'
            );
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil dipindahkan ke tempat sampah.');
    }
}
