<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Permission;
use App\Models\Role;
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
     * Display a listing of the soft-deleted resources.
     */
    public function index(Request $request): Response
    {
        $allowedTypes = ['users', 'roles', 'permissions', 'currencies', 'exchange_rates'];
        $activeTab = in_array($request->input('type'), $allowedTypes)
            ? $request->input('type')
            : 'users';

        $search = $request->input('search');

        $counts = [
            'users' => User::onlyTrashed()->count(),
            'roles' => Role::onlyTrashed()->count(),
            'permissions' => Permission::onlyTrashed()->count(),
            'currencies' => Currency::onlyTrashed()->count(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->count(),
        ];

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
        };

        return Inertia::render('RecycleBin/Index', [
            'items' => $items,
            'counts' => $counts,
            'activeTab' => $activeTab,
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
        match ($type) {
            'users' => User::onlyTrashed()->findOrFail($id)->restore(),
            'roles' => Role::onlyTrashed()->findOrFail($id)->restore(),
            'permissions' => Permission::onlyTrashed()->findOrFail($id)->restore(),
            'currencies' => Currency::onlyTrashed()->findOrFail($id)->restore(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->findOrFail($id)->restore(),
            default => abort(404),
        };

        return redirect()->back()->with('success', 'Data berhasil dipulihkan dari tempat sampah.');
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(string $type, string $id): RedirectResponse
    {
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
            default => abort(404),
        };

        return redirect()->back()->with('success', 'Data berhasil dihapus secara permanen.');
    }

    /**
     * Restore all soft-deleted resources of the given type.
     */
    public function restoreAll(string $type): RedirectResponse
    {
        $count = match ($type) {
            'users' => User::onlyTrashed()->count(),
            'roles' => Role::onlyTrashed()->count(),
            'permissions' => Permission::onlyTrashed()->count(),
            'currencies' => Currency::onlyTrashed()->count(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->count(),
            default => abort(404),
        };

        match ($type) {
            'users' => User::onlyTrashed()->restore(),
            'roles' => Role::onlyTrashed()->restore(),
            'permissions' => Permission::onlyTrashed()->restore(),
            'currencies' => Currency::onlyTrashed()->restore(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->restore(),
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
        $count = match ($type) {
            'users' => User::onlyTrashed()->count(),
            'roles' => Role::onlyTrashed()->count(),
            'permissions' => Permission::onlyTrashed()->count(),
            'currencies' => Currency::onlyTrashed()->count(),
            'exchange_rates' => ExchangeRate::onlyTrashed()->count(),
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
