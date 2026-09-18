<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarDay extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'calendar_days';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'year',
        'month',
        'day',
        'day_of_week',
        'day_name',
        'is_working_day',
        'type',
        'name',
        'source',
        'is_overridden',
        'updated_by',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'year' => 'integer',
            'month' => 'integer',
            'day' => 'integer',
            'day_of_week' => 'integer',
            'is_working_day' => 'boolean',
            'is_overridden' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke pengguna yang memperbarui data kalender ini.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relasi ke pengguna yang membuat rekaman kalender ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Custom label untuk LogsActivity trait.
     */
    public function getActivityRecordTitle(): string
    {
        return 'hari kalender';
    }

    /**
     * Custom label subjek untuk LogsActivity trait.
     */
    public function getActivitySubjectLabel(): string
    {
        $dateStr = $this->date ? $this->date->format('Y-m-d') : '';
        $status = $this->is_working_day ? 'Hari Kerja' : 'Hari Libur';
        $keterangan = $this->name ? " ({$this->name})" : '';

        return "{$dateStr} [{$this->day_name}] - {$status}{$keterangan}";
    }
}
