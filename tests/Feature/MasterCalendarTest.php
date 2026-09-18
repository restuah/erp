<?php

use App\Models\CalendarDay;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
    $this->user->assignRole($role);
});

test('calendar page initializes default calendar days with weekdays as workdays and weekends as holidays', function () {
    $year = 2026;
    $month = 3;

    $response = $this->actingAs($this->user)->get(route('calendar.index', ['year' => $year, 'month' => $month]));

    $response->assertOk();

    // Pastikan seluruh hari dalam tahun 2026 telah diinisialisasi
    $totalDays = CalendarDay::where('year', $year)->count();
    expect($totalDays)->toBe(365);

    // Cek hari kerja (Senin s/d Jumat)
    $weekday = CalendarDay::where('date', '2026-03-02')->first(); // Senin
    expect($weekday)->not->toBeNull();
    expect($weekday->day_name)->toBe('Senin');
    expect($weekday->is_working_day)->toBeTrue();
    expect($weekday->type)->toBe('workday');

    // Cek akhir pekan (Sabtu & Minggu)
    $saturday = CalendarDay::where('date', '2026-03-07')->first(); // Sabtu
    expect($saturday)->not->toBeNull();
    expect($saturday->is_working_day)->toBeFalse();
    expect($saturday->type)->toBe('weekend');

    $sunday = CalendarDay::where('date', '2026-03-08')->first(); // Minggu
    expect($sunday)->not->toBeNull();
    expect($sunday->is_working_day)->toBeFalse();
    expect($sunday->type)->toBe('weekend');
});

test('syncing calendar with api updates workday into holiday and records user uuid', function () {
    $year = 2026;

    // Mock API tanggalmerah
    Http::fake([
        'https://tanggalmerah.upset.dev/api/holidays*' => Http::response([
            'success' => true,
            'data' => [
                [
                    'date' => '2026-01-01',
                    'day' => 'Kamis',
                    'name' => 'Tahun Baru 2026 Masehi',
                    'type' => 'holiday',
                ],
                [
                    'date' => '2026-03-20',
                    'day' => 'Jumat',
                    'name' => 'Hari Raya Idul Fitri 1447 Hijriyah',
                    'type' => 'leave',
                ],
            ],
            'meta' => [
                'total' => 2,
                'year' => 2026,
            ],
        ], 200),
    ]);

    $response = $this->actingAs($this->user)->post(route('calendar.sync'), [
        'year' => $year,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // 2026-01-01 (Kamis) seharusnya berubah menjadi hari libur nasional
    $newYearDay = CalendarDay::where('date', '2026-01-01')->first();
    expect($newYearDay->is_working_day)->toBeFalse();
    expect($newYearDay->type)->toBe('national_holiday');
    expect($newYearDay->name)->toBe('Tahun Baru 2026 Masehi');
    expect($newYearDay->source)->toBe('api_sync');
    expect($newYearDay->updated_by)->toBe($this->user->id);

    // 2026-03-20 (Jumat) seharusnya berubah menjadi cuti bersama
    $eidDay = CalendarDay::where('date', '2026-03-20')->first();
    expect($eidDay->is_working_day)->toBeFalse();
    expect($eidDay->type)->toBe('collective_leave');
    expect($eidDay->source)->toBe('api_sync');
    expect($eidDay->updated_by)->toBe($this->user->id);
});

test('user can manually change workday into holiday and records user uuid', function () {
    // Inisialisasi
    $this->actingAs($this->user)->get(route('calendar.index', ['year' => 2026, 'month' => 4]));

    $workday = CalendarDay::where('date', '2026-04-15')->first(); // Rabu (hari kerja)
    expect($workday->is_working_day)->toBeTrue();

    $response = $this->actingAs($this->user)->patch(route('calendar.update', $workday->id), [
        'is_working_day' => false,
        'name' => 'Libur Perusahaan (Family Gathering)',
        'type' => 'custom_holiday',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $workday->refresh();
    expect($workday->is_working_day)->toBeFalse();
    expect($workday->name)->toBe('Libur Perusahaan (Family Gathering)');
    expect($workday->type)->toBe('custom_holiday');
    expect($workday->source)->toBe('manual');
    expect($workday->is_overridden)->toBeTrue();
    expect($workday->updated_by)->toBe($this->user->id);
});

test('user can manually change weekend holiday into workday and records user uuid', function () {
    // Inisialisasi
    $this->actingAs($this->user)->get(route('calendar.index', ['year' => 2026, 'month' => 4]));

    $saturday = CalendarDay::where('date', '2026-04-18')->first(); // Sabtu (weekend)
    expect($saturday->is_working_day)->toBeFalse();

    $response = $this->actingAs($this->user)->patch(route('calendar.update', $saturday->id), [
        'is_working_day' => true,
        'name' => 'Lembur Massal Operasional',
        'type' => 'custom_workday',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $saturday->refresh();
    expect($saturday->is_working_day)->toBeTrue();
    expect($saturday->name)->toBe('Lembur Massal Operasional');
    expect($saturday->type)->toBe('custom_workday');
    expect($saturday->source)->toBe('manual');
    expect($saturday->is_overridden)->toBeTrue();
    expect($saturday->updated_by)->toBe($this->user->id);
});

test('calendar can be reset to default', function () {
    $year = 2026;
    $this->actingAs($this->user)->get(route('calendar.index', ['year' => $year, 'month' => 5]));

    // Modifikasi salah satu hari
    $day = CalendarDay::where('date', '2026-05-06')->first();
    $day->update([
        'is_working_day' => false,
        'name' => 'Custom Holiday',
        'is_overridden' => true,
        'source' => 'manual',
    ]);

    $response = $this->actingAs($this->user)->post(route('calendar.reset'), [
        'year' => $year,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $day->refresh();
    expect($day->is_working_day)->toBeTrue(); // Rabu kembali kerja
    expect($day->source)->toBe('default');
    expect($day->is_overridden)->toBeFalse();
});
