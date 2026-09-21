<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExchangeRate extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'exchange_rates';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'currency_id',
        'currency_code',
        'date',
        'unit',
        'rate_buy',
        'rate_sell',
        'rate_middle',
        'source',
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
            'date' => 'date:Y-m-d',
            'unit' => 'decimal:2',
            'rate_buy' => 'decimal:4',
            'rate_sell' => 'decimal:4',
            'rate_middle' => 'decimal:4',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Interact with currency_code.
     */
    protected function currencyCode(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * Relasi ke master mata uang.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Relasi ke pembuat record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke pengubah record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Record title for LogsActivity trait.
     */
    public function getActivityRecordTitle(): string
    {
        return 'kurs mata uang';
    }

    /**
     * Subject label for LogsActivity trait.
     */
    public function getActivitySubjectLabel(): string
    {
        $dateFormatted = is_string($this->date) ? $this->date : $this->date?->format('d/m/Y');
        return "{$this->currency_code} ({$dateFormatted})";
    }
}
