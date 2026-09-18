<?php

namespace App\Services;

use App\Models\CalendarDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CalendarService
{
    /**
     * Nama hari dalam Bahasa Indonesia berdasarkan dayOfWeekIso (1 = Senin ... 7 = Minggu).
     */
    protected const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    /**
     * Memastikan hari-hari dalam tahun kalender sudah diinisialisasi.
     * Secara default:
     * - Weekday (Senin-Jumat): Hari Kerja (is_working_day = true)
     * - Weekend (Sabtu-Minggu): Hari Libur (is_working_day = false)
     */
    public function ensureYearInitialized(int $year, ?string $userId = null): int
    {
        $existingCount = CalendarDay::where('year', $year)->count();
        $totalDaysInYear = Carbon::createFromDate($year, 1, 1)->isLeapYear() ? 366 : 365;

        if ($existingCount >= $totalDaysInYear) {
            return 0; // Sudah lengkap
        }

        $startDate = Carbon::createFromDate($year, 1, 1);
        $endDate = Carbon::createFromDate($year, 12, 31);
        $period = CarbonPeriod::create($startDate, $endDate);

        $now = now();
        $daysToInsert = [];

        // Ambil tanggal yang sudah ada jika ada sebagian
        $existingDates = CalendarDay::where('year', $year)->pluck('date')->map(function ($d) {
            return $d instanceof Carbon ? $d->format('Y-m-d') : substr((string) $d, 0, 10);
        })->flip();

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            if (isset($existingDates[$dateString])) {
                continue;
            }

            $dayOfWeekIso = $date->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
            $isWeekend = $dayOfWeekIso >= 6;
            $dayName = self::DAY_NAMES[$dayOfWeekIso] ?? $date->locale('id')->isoFormat('dddd');

            $daysToInsert[] = [
                'id' => (string) Str::uuid(),
                'date' => $dateString,
                'year' => $year,
                'month' => $date->month,
                'day' => $date->day,
                'day_of_week' => $dayOfWeekIso,
                'day_name' => $dayName,
                'is_working_day' => ! $isWeekend,
                'type' => $isWeekend ? 'weekend' : 'workday',
                'name' => $isWeekend ? ($dayOfWeekIso === 6 ? 'Akhir Pekan (Sabtu)' : 'Akhir Pekan (Minggu)') : null,
                'source' => 'default',
                'is_overridden' => false,
                'updated_by' => $userId,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($daysToInsert)) {
            // Batch insert chunks
            foreach (array_chunk($daysToInsert, 100) as $chunk) {
                CalendarDay::insert($chunk);
            }
        }

        return count($daysToInsert);
    }

    /**
     * Sinkronisasi data hari libur nasional & cuti bersama dari API tanggalmerah.upset.dev.
     * Mengupdate tanggal hari kerja menjadi hari libur nasional / cuti bersama.
     */
    public function syncFromApi(int $year, ?string $userId = null): array
    {
        // 1. Pastikan seluruh tanggal tahun tersebut sudah ada
        $this->ensureYearInitialized($year, $userId);

        // 2. Ambil data dari API
        $baseUrl = config('services.tanggalmerah.base_url', 'https://tanggalmerah.upset.dev');
        $url = rtrim($baseUrl, '/').'/api/holidays';

        $response = Http::timeout(15)
            ->withHeaders(['Accept' => 'application/json'])
            ->get($url, ['year' => $year]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Gagal terhubung ke API Tanggal Merah ({$url}): HTTP ".$response->status()
            );
        }

        $body = $response->json();
        $holidays = $body['data'] ?? [];
        $syncedCount = 0;

        foreach ($holidays as $holiday) {
            $date = $holiday['date'] ?? null;
            if (! $date) {
                continue;
            }

            $holidayType = ($holiday['type'] ?? 'holiday') === 'leave'
                ? 'collective_leave'
                : 'national_holiday';

            $holidayName = $holiday['name'] ?? 'Hari Libur Nasional';

            $day = CalendarDay::where('date', $date)->first();
            if ($day) {
                $day->update([
                    'is_working_day' => false, // Diubah menjadi hari libur
                    'type' => $holidayType,
                    'name' => $holidayName,
                    'source' => 'api_sync',
                    'updated_by' => $userId,
                ]);
                $syncedCount++;
            }
        }

        // Catat aktivitas log sistem
        ActivityLogger::log(
            description: "Sinkronisasi {$syncedCount} hari libur & cuti bersama kalender tahun {$year} dari API Tanggal Merah",
            event: 'sync',
            logName: 'calendar',
            properties: [
                'year' => $year,
                'synced_count' => $syncedCount,
                'api_total' => $body['meta']['total'] ?? count($holidays),
            ],
            status: 'success'
        );

        return [
            'success' => true,
            'year' => $year,
            'synced_count' => $syncedCount,
            'meta' => $body['meta'] ?? null,
        ];
    }

    /**
     * Memperbarui hari kalender secara manual (mengubah hari kerja ke libur atau sebaliknya).
     */
    public function updateDay(CalendarDay $day, array $data, string $userId): CalendarDay
    {
        $isWorkingDay = (bool) ($data['is_working_day'] ?? $day->is_working_day);
        $name = isset($data['name']) ? trim($data['name']) : $day->name;

        // Tentukan tipe hari
        if ($isWorkingDay) {
            // Diubah jadi hari kerja
            $type = ($day->day_of_week >= 6) ? 'custom_workday' : 'workday';
            if (empty($name) && $type === 'workday') {
                $name = null;
            }
        } else {
            // Diubah jadi hari libur
            if ($day->day_of_week >= 6) {
                $type = 'weekend';
                $name = $name ?: ($day->day_of_week === 6 ? 'Akhir Pekan (Sabtu)' : 'Akhir Pekan (Minggu)');
            } else {
                $type = $data['type'] ?? 'custom_holiday';
                $name = $name ?: 'Hari Libur Khusus';
            }
        }

        $day->update([
            'is_working_day' => $isWorkingDay,
            'type' => $type,
            'name' => $name,
            'source' => 'manual',
            'is_overridden' => true,
            'updated_by' => $userId,
        ]);

        return $day->fresh(['updater:id,name,email,avatar']);
    }

    /**
     * Mengembalikan kalender tahun tertentu ke kondisi default (weekday = kerja, weekend = libur).
     */
    public function resetYearToDefault(int $year, string $userId): int
    {
        $this->ensureYearInitialized($year, $userId);

        $days = CalendarDay::where('year', $year)->get();
        $resetCount = 0;

        foreach ($days as $day) {
            $isWeekend = $day->day_of_week >= 6;
            $defaultType = $isWeekend ? 'weekend' : 'workday';
            $defaultName = $isWeekend ? ($day->day_of_week === 6 ? 'Akhir Pekan (Sabtu)' : 'Akhir Pekan (Minggu)') : null;
            $defaultWorkingDay = ! $isWeekend;

            if (
                $day->is_working_day !== $defaultWorkingDay ||
                $day->type !== $defaultType ||
                $day->name !== $defaultName ||
                $day->is_overridden ||
                $day->source !== 'default'
            ) {
                $day->update([
                    'is_working_day' => $defaultWorkingDay,
                    'type' => $defaultType,
                    'name' => $defaultName,
                    'source' => 'default',
                    'is_overridden' => false,
                    'updated_by' => $userId,
                ]);
                $resetCount++;
            }
        }

        ActivityLogger::log(
            description: "Reset {$resetCount} hari kalender tahun {$year} kembali ke pengaturan default kerja/libur",
            event: 'reset',
            logName: 'calendar',
            properties: [
                'year' => $year,
                'reset_count' => $resetCount,
            ],
            status: 'info'
        );

        return $resetCount;
    }
}
