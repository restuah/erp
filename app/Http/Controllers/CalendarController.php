<?php

namespace App\Http\Controllers;

use App\Models\CalendarDay;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Tampilkan data kalender kerja dan libur.
     */
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $viewMode = $request->input('view', 'grid'); // 'grid' atau 'table'
        $statusFilter = $request->input('status'); // 'workday', 'holiday', 'overridden'
        $search = $request->input('search');

        // Pastikan hari kalender untuk tahun yang diminta sudah diinisialisasi
        $this->calendarService->ensureYearInitialized($year, Auth::id());

        // Query hari untuk bulan yang dipilih
        $daysQuery = CalendarDay::query()
            ->with('updater:id,name,email,avatar')
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('date');

        if ($statusFilter === 'workday') {
            $daysQuery->where('is_working_day', true);
        } elseif ($statusFilter === 'holiday') {
            $daysQuery->where('is_working_day', false);
        } elseif ($statusFilter === 'overridden') {
            $daysQuery->where('is_overridden', true);
        }

        if ($search) {
            $daysQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('date', 'like', "%{$search}%")
                    ->orWhere('day_name', 'like', "%{$search}%");
            });
        }

        $monthDays = $daysQuery->get();

        // Statistik bulan ini
        $allMonthDays = CalendarDay::where('year', $year)->where('month', $month)->get();
        $monthStats = [
            'total_days' => $allMonthDays->count(),
            'workdays_count' => $allMonthDays->where('is_working_day', true)->count(),
            'holidays_count' => $allMonthDays->where('is_working_day', false)->count(),
            'national_holidays_count' => $allMonthDays->whereIn('type', ['national_holiday', 'collective_leave'])->count(),
            'overridden_count' => $allMonthDays->where('is_overridden', true)->count(),
        ];

        // Statistik tahun ini
        $yearStats = [
            'workdays_count' => CalendarDay::where('year', $year)->where('is_working_day', true)->count(),
            'holidays_count' => CalendarDay::where('year', $year)->where('is_working_day', false)->count(),
            'national_holidays_count' => CalendarDay::where('year', $year)->whereIn('type', ['national_holiday', 'collective_leave'])->count(),
            'overridden_count' => CalendarDay::where('year', $year)->where('is_overridden', true)->count(),
        ];

        // Hitung offset hari pertama bulan untuk rendering kalender grid (Minggu = 0 s/d Sabtu = 6)
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1);
        $firstDayOfWeek = $firstDayOfMonth->dayOfWeek; // 0 (Sun) to 6 (Sat)
        $firstDayOfWeekIso = $firstDayOfMonth->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        $daysInMonth = $firstDayOfMonth->daysInMonth;

        $monthsList = [
            ['value' => 1, 'label' => 'Januari'],
            ['value' => 2, 'label' => 'Februari'],
            ['value' => 3, 'label' => 'Maret'],
            ['value' => 4, 'label' => 'April'],
            ['value' => 5, 'label' => 'Mei'],
            ['value' => 6, 'label' => 'Juni'],
            ['value' => 7, 'label' => 'Juli'],
            ['value' => 8, 'label' => 'Agustus'],
            ['value' => 9, 'label' => 'September'],
            ['value' => 10, 'label' => 'Oktober'],
            ['value' => 11, 'label' => 'November'],
            ['value' => 12, 'label' => 'Desember'],
        ];

        // Pilihan daftar tahun (2 tahun ke belakang s/d 5 tahun ke depan)
        $currentYear = now()->year;
        $yearsList = range($currentYear - 3, $currentYear + 5);

        return Inertia::render('Calendar/Index', [
            'days' => $monthDays,
            'currentYear' => $year,
            'currentMonth' => $month,
            'firstDayOfWeek' => $firstDayOfWeek,
            'firstDayOfWeekIso' => $firstDayOfWeekIso,
            'daysInMonth' => $daysInMonth,
            'monthStats' => $monthStats,
            'yearStats' => $yearStats,
            'monthsList' => $monthsList,
            'yearsList' => $yearsList,
            'filters' => [
                'view' => $viewMode,
                'status' => $statusFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Sinkronisasi data hari libur nasional dengan API tanggalmerah.upset.dev.
     */
    public function sync(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $year = (int) $validated['year'];

        try {
            $result = $this->calendarService->syncFromApi($year, Auth::id());

            return back()->with(
                'success',
                "Berhasil mensinkronkan {$result['synced_count']} hari libur nasional & cuti bersama tahun {$year} dari API Tanggal Merah."
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal sinkronisasi API: '.$e->getMessage());
        }
    }

    /**
     * Perbarui status hari kerja/libur secara manual (beserta keterangan).
     */
    public function update(Request $request, CalendarDay $calendarDay): RedirectResponse
    {
        $validated = $request->validate([
            'is_working_day' => 'required|boolean',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
        ]);

        $this->calendarService->updateDay($calendarDay, $validated, Auth::id());

        $dateFormatted = Carbon::parse($calendarDay->date)->translatedFormat('d F Y');
        $statusName = $calendarDay->is_working_day ? 'Hari Kerja' : 'Hari Libur';

        return back()->with(
            'success',
            "Tanggal {$dateFormatted} ({$calendarDay->day_name}) berhasil diubah menjadi {$statusName}."
        );
    }

    /**
     * Reset kalender tahun ke kondisi bawaan sistem.
     */
    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $year = (int) $validated['year'];
        $count = $this->calendarService->resetYearToDefault($year, Auth::id());

        return back()->with(
            'success',
            "Pengaturan kalender tahun {$year} berhasil di-reset ke default ({$count} hari disesuaikan)."
        );
    }
}
