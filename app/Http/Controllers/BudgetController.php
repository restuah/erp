<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetChecker;
use App\Models\BudgetClassification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $classificationId = $request->input('classification_id');

        $budgets = Budget::query()
            ->with([
                'classification:id,name',
                'pic:id,name,email',
                'checkers' => function ($query) {
                    $query->orderBy('order', 'asc')->with('user:id,name,email');
                },
                'creator:id,name',
                'updater:id,name',
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('pic', function ($picQuery) use ($search) {
                            $picQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('classification', function ($classQuery) use ($search) {
                            $classQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', filter_var($status, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($classificationId, function ($query, $classificationId) {
                $query->where('budget_classification_id', $classificationId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $users = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $classifications = BudgetClassification::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $stats = [
            'total' => Budget::count(),
            'active' => Budget::where('is_active', true)->count(),
            'inactive' => Budget::where('is_active', false)->count(),
            'total_pic' => Budget::distinct('pic_id')->count('pic_id'),
        ];

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgets,
            'users' => $users,
            'classifications' => $classifications,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'classification_id' => $classificationId,
            ],
            'suggestedCode' => $this->generateNextCode(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:budgets,code'],
            'name' => ['required', 'string', 'max:255'],
            'budget_classification_id' => ['nullable', 'uuid', 'exists:budget_classifications,id'],
            'pic_id' => ['required', 'uuid', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'checkers' => ['nullable', 'array'],
            'checkers.*.user_id' => [
                'required',
                'uuid',
                'exists:users,id',
                'distinct',
            ],
            'checkers.*.role_title' => ['nullable', 'string', 'max:100'],
        ], [
            'code.required' => 'Kode budget wajib diisi.',
            'code.unique' => 'Kode budget ini sudah digunakan.',
            'name.required' => 'Nama budget wajib diisi.',
            'budget_classification_id.exists' => 'Klasifikasi budget yang dipilih tidak valid.',
            'pic_id.required' => 'PIC Budget (Owner Anggaran) wajib dipilih.',
            'pic_id.exists' => 'User PIC yang dipilih tidak valid.',
            'checkers.*.user_id.required' => 'Pemeriksa (Checker) wajib dipilih untuk setiap baris level.',
            'checkers.*.user_id.distinct' => 'Pemeriksa tidak boleh duplikat dalam urutan approval yang sama.',
            'checkers.*.user_id.exists' => 'User pemeriksa tidak valid.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $budget = Budget::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'budget_classification_id' => $validated['budget_classification_id'] ?? null,
                'pic_id' => $validated['pic_id'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if (!empty($validated['checkers'])) {
                foreach (array_values($validated['checkers']) as $index => $checkerData) {
                    BudgetChecker::create([
                        'budget_id' => $budget->id,
                        'user_id' => $checkerData['user_id'],
                        'order' => $index + 1,
                        'role_title' => !empty($checkerData['role_title']) 
                            ? $checkerData['role_title'] 
                            : 'Checker ' . ($index + 1),
                    ]);
                }
            }
        });

        return redirect()->route('budgets.index')->with('success', 'Master Budget berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('budgets', 'code')->ignore($budget->id)],
            'name' => ['required', 'string', 'max:255'],
            'budget_classification_id' => ['nullable', 'uuid', 'exists:budget_classifications,id'],
            'pic_id' => ['required', 'uuid', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'checkers' => ['nullable', 'array'],
            'checkers.*.user_id' => [
                'required',
                'uuid',
                'exists:users,id',
                'distinct',
            ],
            'checkers.*.role_title' => ['nullable', 'string', 'max:100'],
        ], [
            'code.required' => 'Kode budget wajib diisi.',
            'code.unique' => 'Kode budget ini sudah digunakan oleh data lain.',
            'name.required' => 'Nama budget wajib diisi.',
            'budget_classification_id.exists' => 'Klasifikasi budget yang dipilih tidak valid.',
            'pic_id.required' => 'PIC Budget (Owner Anggaran) wajib dipilih.',
            'pic_id.exists' => 'User PIC yang dipilih tidak valid.',
            'checkers.*.user_id.required' => 'Pemeriksa (Checker) wajib dipilih untuk setiap baris level.',
            'checkers.*.user_id.distinct' => 'Pemeriksa tidak boleh duplikat dalam urutan approval yang sama.',
            'checkers.*.user_id.exists' => 'User pemeriksa tidak valid.',
        ]);

        DB::transaction(function () use ($budget, $validated, $request) {
            $budget->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'budget_classification_id' => $validated['budget_classification_id'] ?? null,
                'pic_id' => $validated['pic_id'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'updated_by' => Auth::id(),
            ]);

            // Sync dynamic checkers
            $budget->checkers()->delete();

            if (!empty($validated['checkers'])) {
                foreach (array_values($validated['checkers']) as $index => $checkerData) {
                    BudgetChecker::create([
                        'budget_id' => $budget->id,
                        'user_id' => $checkerData['user_id'],
                        'order' => $index + 1,
                        'role_title' => !empty($checkerData['role_title'])
                            ? $checkerData['role_title']
                            : 'Checker ' . ($index + 1),
                    ]);
                }
            }
        });

        return redirect()->route('budgets.index')->with('success', 'Master Budget berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget): RedirectResponse
    {
        $budget->delete();

        return redirect()->route('budgets.index')->with('success', 'Master Budget berhasil dihapus.');
    }

    /**
     * Endpoint to generate the next suggested budget code.
     */
    public function generateCode(): JsonResponse
    {
        return response()->json([
            'code' => $this->generateNextCode(),
        ]);
    }

    /**
     * Helper to compute next sequential budget code: BDG-YYYY-XXXX
     */
    protected function generateNextCode(): string
    {
        $year = date('Y');
        $prefix = "BDG-{$year}-";

        $latestBudget = Budget::withTrashed()
            ->where('code', 'like', "{$prefix}%")
            ->orderBy('code', 'desc')
            ->first();

        if ($latestBudget) {
            $lastNumber = (int) substr($latestBudget->code, strlen($prefix));
            $nextNumber = str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$prefix}{$nextNumber}";
    }
}
