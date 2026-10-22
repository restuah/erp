<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'suppliers';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_type',
        'supplier_code',
        'supplier_name',
        'status',
        'term_of_payment_id',
        'address',
        'zipcode',
        'npwp',
        'office_phone',
        'fax',
        'marketing_name',
        'marketing_phone',
        'marketing_email',
        'accounting_name',
        'accounting_phone',
        'accounting_email',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'default_currency',
        'country_code',
        'delivery_period',
        'delivery_time',
        'delivery_end_time',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Standardize supplier code to uppercase.
     */
    protected function supplierCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * Standardize default currency to uppercase.
     */
    protected function defaultCurrency(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : 'IDR',
        );
    }

    /**
     * Standardize country code to uppercase.
     */
    protected function countryCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : 'ID',
        );
    }

    /**
     * Relasi ke Master Term of Payment.
     */
    public function termOfPayment(): BelongsTo
    {
        return $this->belongsTo(TermOfPayment::class, 'term_of_payment_id');
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
     * Scope query untuk tipe Direct.
     */
    public function scopeDirect(Builder $query): Builder
    {
        return $query->where('supplier_type', 'direct');
    }

    /**
     * Scope query untuk tipe Indirect.
     */
    public function scopeIndirect(Builder $query): Builder
    {
        return $query->where('supplier_type', 'indirect');
    }

    /**
     * Scope query untuk supplier aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Log name for LogsActivity trait.
     */
    public function getActivityLogName(): string
    {
        return 'supplier';
    }

    /**
     * Record title for LogsActivity trait.
     */
    public function getActivityRecordTitle(): string
    {
        return 'supplier master';
    }

    /**
     * Subject label for LogsActivity trait.
     */
    public function getActivitySubjectLabel(): string
    {
        $typeLabel = ucfirst($this->supplier_type);
        return "{$this->supplier_code} - {$this->supplier_name} ({$typeLabel})";
    }
}
