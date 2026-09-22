<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $kategori = $request->input('kategori');
        $jenis = $request->input('jenis');
        $postable = $request->input('postable');
        $level = $request->input('level');
        $viewMode = $request->input('view_mode', 'table'); // 'table' or 'tree'

        // Base query for Table View
        $query = ChartOfAccount::query()
            ->with(['parent:id,account_code,account_name', 'creator:id,name', 'updater:id,name'])
            ->withCount('children')
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('account_code', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%")
                        ->orWhere('parent_code', 'like', "%{$search}%");
                });
            })
            ->when($kategori, function ($q, $kategori) {
                $q->where('kategori', $kategori);
            })
            ->when($jenis, function ($q, $jenis) {
                $q->where('jenis', $jenis);
            })
            ->when($postable !== null && $postable !== '', function ($q) use ($postable) {
                $q->where('postable', filter_var($postable, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($level, function ($q, $level) {
                $q->where('level', $level);
            });

        $accounts = $query->orderBy('account_code')->paginate(15)->withQueryString();

        // Tree structure for Tree View
        $treeAccounts = $this->buildTreeData($search, $kategori, $jenis, $postable);

        // Stats summary
        $stats = [
            'total' => ChartOfAccount::count(),
            'postable' => ChartOfAccount::where('postable', true)->count(),
            'header' => ChartOfAccount::where('postable', false)->count(),
            'bs' => ChartOfAccount::where('kategori', 'bs')->count(),
            'pl' => ChartOfAccount::where('kategori', 'pl')->count(),
        ];

        // Available parent accounts for create/edit form
        $availableParents = ChartOfAccount::query()
            ->select('id', 'account_code', 'account_name', 'level', 'kategori', 'jenis')
            ->orderBy('account_code')
            ->get();

        return Inertia::render('ChartOfAccounts/Index', [
            'accounts' => $accounts,
            'treeAccounts' => $treeAccounts,
            'stats' => $stats,
            'availableParents' => $availableParents,
            'filters' => [
                'search' => $search,
                'kategori' => $kategori,
                'jenis' => $jenis,
                'postable' => $postable,
                'level' => $level,
                'view_mode' => $viewMode,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chart_of_accounts', 'account_code')->whereNull('deleted_at'),
            ],
            'account_name' => ['required', 'string', 'max:200'],
            'parent_code' => ['nullable', 'string', 'max:50', 'exists:chart_of_accounts,account_code'],
            'jenis' => ['required', 'in:debit,credit'],
            'kategori' => ['required', 'in:pl,bs'],
            'postable' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'account_code.required' => 'Kode akun wajib diisi.',
            'account_code.unique' => 'Kode akun ini sudah digunakan.',
            'account_name.required' => 'Nama akun wajib diisi.',
            'parent_code.exists' => 'Akun parent yang dipilih tidak valid.',
            'jenis.required' => 'Jenis saldo normal (Debit/Kredit) wajib dipilih.',
            'kategori.required' => 'Kategori (PL/BS) wajib dipilih.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $parent = null;
            $level = 1;
            $parentId = null;
            $parentCode = null;

            if (!empty($validated['parent_code'])) {
                $parent = ChartOfAccount::where('account_code', $validated['parent_code'])->first();
                if ($parent) {
                    $level = $parent->level + 1;
                    $parentId = $parent->id;
                    $parentCode = $parent->account_code;

                    // Automatically make parent account postable = false
                    if ($parent->postable) {
                        $parent->update(['postable' => false]);
                    }
                }
            }

            // New account is postable by default if it has no children yet
            $postable = $request->boolean('postable', true);

            ChartOfAccount::create([
                'account_code' => trim($validated['account_code']),
                'account_name' => trim($validated['account_name']),
                'level' => $level,
                'parent_code' => $parentCode,
                'parent_id' => $parentId,
                'jenis' => $validated['jenis'],
                'kategori' => $validated['kategori'],
                'postable' => $postable,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun COA berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $validated = $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('chart_of_accounts', 'account_code')
                    ->ignore($chartOfAccount->id)
                    ->whereNull('deleted_at'),
            ],
            'account_name' => ['required', 'string', 'max:200'],
            'parent_code' => ['nullable', 'string', 'max:50', 'exists:chart_of_accounts,account_code'],
            'jenis' => ['required', 'in:debit,credit'],
            'kategori' => ['required', 'in:pl,bs'],
            'postable' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ], [
            'account_code.required' => 'Kode akun wajib diisi.',
            'account_code.unique' => 'Kode akun ini sudah digunakan.',
            'account_name.required' => 'Nama akun wajib diisi.',
            'parent_code.exists' => 'Akun parent yang dipilih tidak valid.',
            'jenis.required' => 'Jenis saldo normal (Debit/Kredit) wajib dipilih.',
            'kategori.required' => 'Kategori (PL/BS) wajib dipilih.',
        ]);

        DB::transaction(function () use ($validated, $request, $chartOfAccount) {
            $oldParentId = $chartOfAccount->parent_id;
            $parent = null;
            $newLevel = 1;
            $newParentId = null;
            $newParentCode = null;

            if (!empty($validated['parent_code'])) {
                $parent = ChartOfAccount::where('account_code', $validated['parent_code'])->first();
                if ($parent) {
                    // Prevent circular hierarchy
                    if ($parent->id === $chartOfAccount->id) {
                        throw ValidationException::withMessages([
                            'parent_code' => 'Akun tidak dapat memilih dirinya sendiri sebagai parent.',
                        ]);
                    }

                    $descendants = $chartOfAccount->getAllDescendantIds();
                    if (in_array($parent->id, $descendants)) {
                        throw ValidationException::withMessages([
                            'parent_code' => 'Akun tidak dapat memilih sub-akun/keturunannya sebagai parent.',
                        ]);
                    }

                    $newLevel = $parent->level + 1;
                    $newParentId = $parent->id;
                    $newParentCode = $parent->account_code;

                    // New parent must become non-postable
                    if ($parent->postable) {
                        $parent->update(['postable' => false]);
                    }
                }
            }

            // If this account has any children, postable MUST be false
            $hasChildren = $chartOfAccount->children()->exists();
            $postable = $hasChildren ? false : $request->boolean('postable', true);

            $chartOfAccount->update([
                'account_code' => trim($validated['account_code']),
                'account_name' => trim($validated['account_name']),
                'level' => $newLevel,
                'parent_code' => $newParentCode,
                'parent_id' => $newParentId,
                'jenis' => $validated['jenis'],
                'kategori' => $validated['kategori'],
                'postable' => $postable,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
                'updated_by' => Auth::id(),
            ]);

            // If account_code changed, update parent_code in all direct children
            if ($chartOfAccount->wasChanged('account_code')) {
                ChartOfAccount::where('parent_id', $chartOfAccount->id)
                    ->update(['parent_code' => $chartOfAccount->account_code]);
            }

            // Recursively update levels of all descendants if level changed
            $this->recalculateChildrenLevels($chartOfAccount);

            // If parent changed, check if old parent has no more children and restore postable
            if ($oldParentId && $oldParentId !== $newParentId) {
                $oldParentHasChildren = ChartOfAccount::where('parent_id', $oldParentId)->exists();
                if (!$oldParentHasChildren) {
                    ChartOfAccount::where('id', $oldParentId)->update(['postable' => true]);
                }
            }
        });

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun COA berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        // Prevent deletion if account still has children
        if ($chartOfAccount->children()->exists()) {
            return back()->with('error', 'Akun tidak dapat dihapus karena masih memiliki sub-akun (child). Harap hapus atau pindahkan sub-akun terlebih dahulu.');
        }

        DB::transaction(function () use ($chartOfAccount) {
            $parentId = $chartOfAccount->parent_id;
            $chartOfAccount->delete();

            // If parent now has no active children, restore postable to true
            if ($parentId) {
                $parentHasOtherChildren = ChartOfAccount::where('parent_id', $parentId)->exists();
                if (!$parentHasOtherChildren) {
                    ChartOfAccount::where('id', $parentId)->update(['postable' => true]);
                }
            }
        });

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun COA berhasil dihapus.');
    }

    /**
     * Recalculate levels of all child accounts recursively.
     */
    private function recalculateChildrenLevels(ChartOfAccount $parent): void
    {
        $children = ChartOfAccount::where('parent_id', $parent->id)->get();
        foreach ($children as $child) {
            $child->level = $parent->level + 1;
            $child->saveQuietly();
            $this->recalculateChildrenLevels($child);
        }
    }

    /**
     * Build nested tree data for Tree View.
     */
    private function buildTreeData(?string $search, ?string $kategori, ?string $jenis, $postable): array
    {
        $allAccounts = ChartOfAccount::query()
            ->withCount('children')
            ->orderBy('account_code')
            ->get();

        // If filters are present, mark accounts matching filter or having matching descendants
        $filterActive = !empty($search) || !empty($kategori) || !empty($jenis) || ($postable !== null && $postable !== '');

        $accountsById = [];
        foreach ($allAccounts as $acc) {
            $accountsById[$acc->id] = [
                'id' => $acc->id,
                'account_code' => $acc->account_code,
                'account_name' => $acc->account_name,
                'level' => $acc->level,
                'parent_code' => $acc->parent_code,
                'parent_id' => $acc->parent_id,
                'jenis' => $acc->jenis,
                'kategori' => $acc->kategori,
                'postable' => $acc->postable,
                'description' => $acc->description,
                'is_active' => $acc->is_active,
                'children_count' => $acc->children_count,
                'children' => [],
                'matches_filter' => true,
            ];
        }

        if ($filterActive) {
            $searchLower = strtolower($search ?? '');
            $postableBool = ($postable !== null && $postable !== '') ? filter_var($postable, FILTER_VALIDATE_BOOLEAN) : null;

            foreach ($accountsById as $id => &$acc) {
                $matches = true;
                if (!empty($search)) {
                    $codeMatch = str_contains(strtolower($acc['account_code']), $searchLower);
                    $nameMatch = str_contains(strtolower($acc['account_name']), $searchLower);
                    if (!$codeMatch && !$nameMatch) {
                        $matches = false;
                    }
                }
                if ($kategori && $acc['kategori'] !== $kategori) {
                    $matches = false;
                }
                if ($jenis && $acc['jenis'] !== $jenis) {
                    $matches = false;
                }
                if ($postableBool !== null && $acc['postable'] !== $postableBool) {
                    $matches = false;
                }
                $acc['matches_filter'] = $matches;
            }
            unset($acc);
        }

        // Build tree
        $tree = [];
        foreach ($accountsById as $id => &$acc) {
            if ($acc['parent_id'] && isset($accountsById[$acc['parent_id']])) {
                $accountsById[$acc['parent_id']]['children'][] = &$acc;
            } else {
                $tree[] = &$acc;
            }
        }
        unset($acc);

        return $tree;
    }
}
