<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'budgets';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'budget_classification_id',
        'pic_id',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Standardize code to uppercase.
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * Relasi ke klasifikasi anggaran (nullable).
     */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(BudgetClassification::class, 'budget_classification_id');
    }

    /**
     * Relasi ke user PIC Anggaran (Owner Anggaran).
     */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    /**
     * Relasi ke daftar dinamis approval checkers.
     */
    public function checkers(): HasMany
    {
        return $this->hasMany(BudgetChecker::class, 'budget_id')->orderBy('order', 'asc');
    }

    /**
     * Relasi ke user pembuat data.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user yang memperbarui data.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Log name for LogsActivity trait.
     */
    public function getActivityLogName(): string
    {
        return 'budget';
    }

    /**
     * Record title for LogsActivity trait.
     */
    public function getActivityRecordTitle(): string
    {
        return 'budget master';
    }

    /**
     * Subject label for LogsActivity trait.
     */
    public function getActivitySubjectLabel(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
