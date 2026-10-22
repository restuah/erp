<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'customers';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_code',
        'name',
        'alias',
        'tax_id',
        'term_of_payment_id',
        'currency_id',
        'coa_receivable_id',
        'phone',
        'fax',
        'email',
        'contact_person',
        'contact_person_phone',
        'contact_person_email',
        'billing_address',
        'shipping_address',
        'tax_address',
        'delivery_lead_time_days',
        'is_active',
        'notes',
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
            'delivery_lead_time_days' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Standardize customer code to uppercase.
     */
    protected function customerCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * Relasi ke Master Term of Payment (Syarat Pembayaran).
     */
    public function termOfPayment(): BelongsTo
    {
        return $this->belongsTo(TermOfPayment::class, 'term_of_payment_id');
    }

    /**
     * Relasi ke Master Currency (Mata Uang Default).
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relasi ke Chart of Account (Akun Piutang Usaha / AR).
     */
    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_receivable_id');
    }

    /**
     * Relasi ke daftar rekening bank milik customer.
     */
    public function banks(): HasMany
    {
        return $this->hasMany(CustomerBank::class, 'customer_id');
    }

    /**
     * Relasi ke rekening bank utama (primary).
     */
    public function primaryBank(): HasOne
    {
        return $this->hasOne(CustomerBank::class, 'customer_id')->where('is_primary', true);
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
     * Scope query untuk customer aktif.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Log name for LogsActivity trait.
     */
    public function getActivityLogName(): string
    {
        return 'customer';
    }

    /**
     * Record title for LogsActivity trait.
     */
    public function getActivityRecordTitle(): string
    {
        return 'customer master';
    }

    /**
     * Subject label for LogsActivity trait.
     */
    public function getActivitySubjectLabel(): string
    {
        $aliasText = $this->alias ? " ({$this->alias})" : '';
        return "{$this->customer_code} - {$this->name}{$aliasText}";
    }
}
