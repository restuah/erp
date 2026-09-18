<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $event = $request->input('event');
        $module = $request->input('module');
        $causerId = $request->input('causer_id');
        $range = $request->input('range', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Main Query
        $logs = ActivityLog::query()
            ->search($search)
            ->filterByEvent($event)
            ->filterByModule($module)
            ->filterByCauser($causerId)
            ->filterByDateRange($range, $startDate, $endDate)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Summary Statistics
        $totalActivities = ActivityLog::count();
        $todayActivities = ActivityLog::whereDate('created_at', Carbon::today())->count();
        $criticalActivities = ActivityLog::whereIn('event', ['deleted', 'force_deleted', 'empty_trash'])->count();

        $topUserRecord = ActivityLog::whereDate('created_at', Carbon::today())
            ->whereNotNull('causer_name')
            ->select('causer_name', 'causer_email', DB::raw('count(*) as total_count'))
            ->groupBy('causer_name', 'causer_email')
            ->orderByDesc('total_count')
            ->first();

        // Available Filter Options
        $availableModules = ActivityLog::select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->orderBy('log_name')
            ->pluck('log_name');

        $availableEvents = [
            'created' => 'Dibuat (Created)',
            'updated' => 'Diperbarui (Updated)',
            'deleted' => 'Dihapus (Soft Delete)',
            'restored' => 'Dipulihkan (Restored)',
            'force_deleted' => 'Dihapus Permanen',
            'login' => 'Masuk (Login)',
            'logout' => 'Keluar (Logout)',
            'failed_login' => 'Gagal Masuk',
            'password_reset' => 'Atur Ulang Sandi',
            'bulk_restore' => 'Pemulihan Massal',
            'empty_trash' => 'Kosongkan Tempat Sampah',
            'custom' => 'Kustom / Proses Sistem',
        ];

        $availableUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'stats' => [
                'total' => $totalActivities,
                'today' => $todayActivities,
                'critical' => $criticalActivities,
                'top_user' => $topUserRecord ? [
                    'name' => $topUserRecord->causer_name,
                    'email' => $topUserRecord->causer_email,
                    'count' => $topUserRecord->total_count,
                ] : null,
            ],
            'filterOptions' => [
                'events' => $availableEvents,
                'modules' => $availableModules,
                'users' => $availableUsers,
            ],
            'filters' => [
                'search' => $search,
                'event' => $event,
                'module' => $module,
                'causer_id' => $causerId,
                'range' => $range,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Export activity logs to CSV based on current filters.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = $request->input('search');
        $event = $request->input('event');
        $module = $request->input('module');
        $causerId = $request->input('causer_id');
        $range = $request->input('range', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = ActivityLog::query()
            ->search($search)
            ->filterByEvent($event)
            ->filterByModule($module)
            ->filterByCauser($causerId)
            ->filterByDateRange($range, $startDate, $endDate)
            ->latest();

        $fileName = 'activity_logs_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, [
                'Waktu (UTC)',
                'Nama Pengguna',
                'Email Pengguna',
                'Peran',
                'Modul',
                'Peristiwa (Event)',
                'Deskripsi',
                'Subjek Target',
                'Metode',
                'URL',
                'IP Address',
                'Perangkat',
                'Status',
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->causer_name ?? 'Sistem',
                        $log->causer_email ?? '-',
                        $log->causer_role ?? '-',
                        $log->log_name,
                        $log->event,
                        $log->description,
                        $log->subject_label ?? '-',
                        $log->method ?? '-',
                        $log->url ?? '-',
                        $log->ip_address ?? '-',
                        $log->device ?? '-',
                        $log->status,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Purge old activity logs.
     */
    public function clear(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        $days = (int) $validated['days'];
        $count = ActivityLogger::cleanOldLogs($days);

        ActivityLogger::log(
            description: $days === 0
                ? "Membersihkan seluruh log aktivitas ({$count} data dihapus)"
                : "Membersihkan log aktivitas lebih lama dari {$days} hari ({$count} data dihapus)",
            event: 'custom',
            logName: 'system',
            properties: [
                'days_threshold' => $days,
                'deleted_records' => $count,
            ],
            status: 'warning'
        );

        $message = $days === 0
            ? "Seluruh riwayat log aktivitas ({$count} baris) berhasil dibersihkan."
            : "Riwayat log aktivitas yang lebih dari {$days} hari ({$count} baris) berhasil dibersihkan.";

        return redirect()->back()->with('success', $message);
    }
}
