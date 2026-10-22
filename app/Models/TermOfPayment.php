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

class TermOfPayment extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'term_of_payments';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment',
        'name',
        'days',
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
            'days' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Standardize payment code to uppercase trimmed.
     */
    protected function payment(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * Relasi ke daftar supplier yang menggunakan termin ini.
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'term_of_payment_id');
    }

    /**
     * Relasi ke daftar customer yang menggunakan termin ini.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'term_of_payment_id');
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
        return 'term_of_payment';
    }

    /**
     * Record title for LogsActivity trait.
     */
    public function getActivityRecordTitle(): string
    {
        return 'term of payment';
    }

    /**
     * Subject label for LogsActivity trait.
     */
    public function getActivitySubjectLabel(): string
    {
        return "{$this->payment} - {$this->name} ({$this->days} hari)";
    }
}
