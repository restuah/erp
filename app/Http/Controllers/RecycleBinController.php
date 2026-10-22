<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetClassification;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Department;
use App\Models\ExchangeRate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\TermOfPayment;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RecycleBinController extends Controller
{
    /**
     * Map recycle bin resource types to permission prefixes.
     *
     * @var array<string, string>
     */
    protected array $typePermissionMap = [
        'users' => 'users',
        'roles' => 'roles',
        'permissions' => 'permissions',
        'currencies' => 'currencies',
        'exchange_rates' => 'exchange_rates',
        'budgets' => 'budgets',
        'budget_classifications' => 'budget_classifications',
        'chart_of_accounts' => 'coa',
        'unit_of_measures' => 'uom',
        'term_of_payments' => 'term_of_payments',
        'suppliers' => 'suppliers',
        'customers' => 'customers',
        'departments' => 'departments',
    ];

    /**
     * Display a listing of the soft-deleted resources.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperadmin = $user->hasRole('Superadmin');
        $hasGeneralAccess = $isSuperadmin || $user->can('recycle_bin.view');

        // Filter allowed types based on user permissions
        $allowedTypes = array_values(array_filter(
            array_keys($this->typePermissionMap),
            function ($type) use ($user, $hasGeneralAccess) {
                if ($hasGeneralAccess) {
                    return true;
                }
                $perm = $this->typePermissionMap[$type];
                return $user->can("{$perm}.restore") || $user->can("{$perm}.force_delete");
            }
        ));

        // If user has neither Superadmin nor permission for any category, forbid access
        abort_if(empty($allowedTypes), 403, 'Akses ke Tempat Sampah ditolak.');

        $requestedType = $request->input('type');
        $activeTab = in_array($requestedType, $allowedTypes)
            ? $requestedType
            : $allowedTypes[0];

        $currentPerm = $this->typePermissionMap[$activeTab] ?? $activeTab;
        $canRestore = $isSuperadmin || $user->can("{$currentPerm}.restore");
        $canForceDelete = $isSuperadmin || $user->can("{$currentPerm}.force_delete");

        $search = $request->input('search');

        $allCounts = [
            'users' => User::onlyTrashed()->count(),
            'roles' => Role::onlyTrashed()->count(),
            'permissions' => Permission::onlyTrashed()->count(),
            'currencies' => Currency::onlyTrashed()->count(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->count(),
            'budgets' => Budget::onlyTrashed()->count(),
            'budget_classifications' => BudgetClassification::onlyTrashed()->count(),
            'chart_of_accounts' => ChartOfAccount::onlyTrashed()->count(),
            'unit_of_measures' => UnitOfMeasure::onlyTrashed()->count(),
            'term_of_payments' => TermOfPayment::onlyTrashed()->count(),
            'suppliers' => Supplier::onlyTrashed()->count(),
            'customers' => Customer::onlyTrashed()->count(),
            'departments' => Department::onlyTrashed()->count(),
        ];
        $counts = array_intersect_key($allCounts, array_flip($allowedTypes));

        $items = match ($activeTab) {
            'users' => User::onlyTrashed()
                ->with('roles:id,name')
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'roles' => Role::onlyTrashed()
                ->withCount(['permissions', 'users'])
                ->when($search, function ($q, $search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'permissions' => Permission::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('guard_name', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'currencies' => Currency::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'exchange_rates' => ExchangeRate::onlyTrashed()
                ->with('currency:id,code,name')
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('currency_code', 'like', "%{$search}%")
                            ->orWhere('date', 'like', "%{$search}%")
                            ->orWhere('source', 'like', "%{$search}%");
                    });
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'budgets' => Budget::onlyTrashed()
                ->with('pic:id,name,email')
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'budget_classifications' => BudgetClassification::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'chart_of_accounts' => ChartOfAccount::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where('account_code', 'like', "%{$search}%")
                        ->orWhere('account_name', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'unit_of_measures' => UnitOfMeasure::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'term_of_payments' => TermOfPayment::onlyTrashed()
                ->when($search, function ($q, $search) {
                    $q->where('payment', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'suppliers' => Supplier::onlyTrashed()
                ->with('termOfPayment:id,payment,name')
                ->when($search, function ($q, $search) {
                    $q->where('supplier_code', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%")
                        ->orWhere('marketing_name', 'like', "%{$search}%")
                        ->orWhere('accounting_name', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'customers' => Customer::onlyTrashed()
                ->with(['termOfPayment:id,payment,name', 'currency:id,code,name'])
                ->when($search, function ($q, $search) {
                    $q->where('customer_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('alias', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),

            'departments' => Department::onlyTrashed()
                ->with('manager:id,name')
                ->when($search, function ($q, $search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->latest('deleted_at')
                ->paginate(10)
                ->withQueryString(),
        };

        return Inertia::render('RecycleBin/Index', [
            'items' => $items,
            'counts' => $counts,
            'activeTab' => $activeTab,
            'allowedTypes' => $allowedTypes,
            'canRestore' => $canRestore,
            'canForceDelete' => $canForceDelete,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Restore the specified soft-deleted resource.
     */
    public function restore(string $type, string $id): RedirectResponse
    {
        $perm = $this->typePermissionMap[$type] ?? null;
        abort_unless($perm, 404);

        $user = auth()->user();
        abort_unless(
            $user->hasRole('Superadmin') || $user->can("{$perm}.restore"),
            403,
            'Anda tidak memiliki izin untuk memulihkan data ini.'
        );

        match ($type) {
            'users' => User::onlyTrashed()->findOrFail($id)->restore(),
            'roles' => Role::onlyTrashed()->findOrFail($id)->restore(),
            'permissions' => Permission::onlyTrashed()->findOrFail($id)->restore(),
            'currencies' => Currency::onlyTrashed()->findOrFail($id)->restore(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->findOrFail($id)->restore(),
            'budgets' => Budget::onlyTrashed()->findOrFail($id)->restore(),
            'budget_classifications' => BudgetClassification::onlyTrashed()->findOrFail($id)->restore(),
            'chart_of_accounts' => ChartOfAccount::onlyTrashed()->findOrFail($id)->restore(),
            'unit_of_measures' => UnitOfMeasure::onlyTrashed()->findOrFail($id)->restore(),
            'term_of_payments' => TermOfPayment::onlyTrashed()->findOrFail($id)->restore(),
            'suppliers' => Supplier::onlyTrashed()->findOrFail($id)->restore(),
            'customers' => Customer::onlyTrashed()->findOrFail($id)->restore(),
            'departments' => Department::onlyTrashed()->findOrFail($id)->restore(),
            default => abort(404),
        };

        return redirect()->back()->with('success', 'Data berhasil dipulihkan dari tempat sampah.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $type, string $id): RedirectResponse
    {
        $perm = $this->typePermissionMap[$type] ?? null;
        abort_unless($perm, 404);

        $user = auth()->user();
        abort_unless(
            $user->hasRole('Superadmin') || $user->can("{$perm}.force_delete"),
            403,
            'Anda tidak memiliki izin untuk menghapus permanen data ini.'
        );

        match ($type) {
            'users' => (function () use ($id) {
                $user = User::onlyTrashed()->findOrFail($id);
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $user->forceDelete();
            })(),
            'roles' => Role::onlyTrashed()->findOrFail($id)->forceDelete(),
            'permissions' => Permission::onlyTrashed()->findOrFail($id)->forceDelete(),
            'currencies' => Currency::onlyTrashed()->findOrFail($id)->forceDelete(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->findOrFail($id)->forceDelete(),
            'budgets' => Budget::onlyTrashed()->findOrFail($id)->forceDelete(),
            'budget_classifications' => BudgetClassification::onlyTrashed()->findOrFail($id)->forceDelete(),
            'chart_of_accounts' => ChartOfAccount::onlyTrashed()->findOrFail($id)->forceDelete(),
            'unit_of_measures' => UnitOfMeasure::onlyTrashed()->findOrFail($id)->forceDelete(),
            'term_of_payments' => TermOfPayment::onlyTrashed()->findOrFail($id)->forceDelete(),
            'suppliers' => Supplier::onlyTrashed()->findOrFail($id)->forceDelete(),
            'customers' => Customer::onlyTrashed()->findOrFail($id)->forceDelete(),
            'departments' => Department::onlyTrashed()->findOrFail($id)->forceDelete(),
            default => abort(404),
        };

        return redirect()->back()->with('success', 'Data berhasil dihapus secara permanen.');
    }

    /**
     * Restore all soft-deleted resources of the given type.
     */
    public function restoreAll(string $type): RedirectResponse
    {
        $perm = $this->typePermissionMap[$type] ?? null;
        abort_unless($perm, 404);

        $user = auth()->user();
        abort_unless(
            $user->hasRole('Superadmin') || $user->can("{$perm}.restore"),
            403,
            'Anda tidak memiliki izin untuk memulihkan data ini.'
        );

        $count = match ($type) {
            'users' => User::onlyTrashed()->count(),
            'roles' => Role::onlyTrashed()->count(),
            'permissions' => Permission::onlyTrashed()->count(),
            'currencies' => Currency::onlyTrashed()->count(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->count(),
            'budgets' => Budget::onlyTrashed()->count(),
            'budget_classifications' => BudgetClassification::onlyTrashed()->count(),
            'chart_of_accounts' => ChartOfAccount::onlyTrashed()->count(),
            'unit_of_measures' => UnitOfMeasure::onlyTrashed()->count(),
            'term_of_payments' => TermOfPayment::onlyTrashed()->count(),
            'suppliers' => Supplier::onlyTrashed()->count(),
            'customers' => Customer::onlyTrashed()->count(),
            'departments' => Department::onlyTrashed()->count(),
            default => abort(404),
        };

        match ($type) {
            'users' => User::onlyTrashed()->restore(),
            'roles' => Role::onlyTrashed()->restore(),
            'permissions' => Permission::onlyTrashed()->restore(),
            'currencies' => Currency::onlyTrashed()->restore(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->restore(),
            'budgets' => Budget::onlyTrashed()->restore(),
            'budget_classifications' => BudgetClassification::onlyTrashed()->restore(),
            'chart_of_accounts' => ChartOfAccount::onlyTrashed()->restore(),
            'unit_of_measures' => UnitOfMeasure::onlyTrashed()->restore(),
            'term_of_payments' => TermOfPayment::onlyTrashed()->restore(),
            'suppliers' => Supplier::onlyTrashed()->restore(),
            'customers' => Customer::onlyTrashed()->restore(),
            'departments' => Department::onlyTrashed()->restore(),
        };

        ActivityLogger::log(
            description: "Memulihkan semua data ({$count} data) pada kategori tempat sampah: {$type}",
            event: 'bulk_restore',
            logName: 'recycle_bin',
            properties: [
                'category' => $type,
                'restored_count' => $count,
            ],
            status: 'success'
        );

        return redirect()->back()->with('success', 'Semua data di kategori ini berhasil dipulihkan.');
    }

    /**
     * Empty trash (permanently delete all soft-deleted resources of the given type).
     */
    public function empty(string $type): RedirectResponse
    {
        $perm = $this->typePermissionMap[$type] ?? null;
        abort_unless($perm, 404);

        $user = auth()->user();
        abort_unless(
            $user->hasRole('Superadmin') || $user->can("{$perm}.force_delete"),
            403,
            'Anda tidak memiliki izin untuk mengosongkan tempat sampah ini.'
        );

        $count = match ($type) {
            'users' => User::onlyTrashed()->count(),
            'roles' => Role::onlyTrashed()->count(),
            'permissions' => Permission::onlyTrashed()->count(),
            'currencies' => Currency::onlyTrashed()->count(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->count(),
            'budgets' => Budget::onlyTrashed()->count(),
            'budget_classifications' => BudgetClassification::onlyTrashed()->count(),
            'chart_of_accounts' => ChartOfAccount::onlyTrashed()->count(),
            'unit_of_measures' => UnitOfMeasure::onlyTrashed()->count(),
            'term_of_payments' => TermOfPayment::onlyTrashed()->count(),
            'suppliers' => Supplier::onlyTrashed()->count(),
            'customers' => Customer::onlyTrashed()->count(),
            'departments' => Department::onlyTrashed()->count(),
            default => abort(404),
        };

        match ($type) {
            'users' => (function () {
                $users = User::onlyTrashed()->get();
                foreach ($users as $user) {
                    if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                        Storage::disk('public')->delete($user->avatar);
                    }
                    $user->forceDelete();
                }
            })(),
            'roles' => Role::onlyTrashed()->forceDelete(),
            'permissions' => Permission::onlyTrashed()->forceDelete(),
            'currencies' => Currency::onlyTrashed()->forceDelete(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->forceDelete(),
            'budgets' => Budget::onlyTrashed()->forceDelete(),
            'budget_classifications' => BudgetClassification::onlyTrashed()->forceDelete(),
            'chart_of_accounts' => ChartOfAccount::onlyTrashed()->forceDelete(),
            'unit_of_measures' => UnitOfMeasure::onlyTrashed()->forceDelete(),
            'term_of_payments' => TermOfPayment::onlyTrashed()->forceDelete(),
            'suppliers' => Supplier::onlyTrashed()->forceDelete(),
            'customers' => Customer::onlyTrashed()->forceDelete(),
            'departments' => Department::onlyTrashed()->forceDelete(),
        };

        ActivityLogger::log(
            description: "Mengosongkan tempat sampah ({$count} data dihapus permanen) pada kategori: {$type}",
            event: 'empty_trash',
            logName: 'recycle_bin',
            properties: [
                'category' => $type,
                'deleted_count' => $count,
            ],
            status: 'warning'
        );

        return redirect()->back()->with('success', 'Tempat sampah pada kategori ini berhasil dikosongkan.');
    }
}
