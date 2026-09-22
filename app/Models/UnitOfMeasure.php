<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitOfMeasure extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'unit_of_measures';

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
        'symbol',
        'category',
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
     * Interact with the unit code attribute.
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? strtoupper(trim($value)) : null,
        );
    }

    /**
     * User pembuat data.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User pengubah data terakhir.
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
        return 'satuan (UOM)';
    }

    /**
     * Subject label for LogsActivity trait.
     */
    public function getActivitySubjectLabel(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Category list with Indonesian labels and badge colors.
     *
     * @return array<string, array{label: string, color: string}>
     */
    public static function categories(): array
    {
        return [
            'count' => [
                'label' => 'Kuantitas / Unit',
                'color' => 'blue',
            ],
            'weight' => [
                'label' => 'Berat',
                'color' => 'emerald',
            ],
            'length' => [
                'label' => 'Panjang',
                'color' => 'amber',
            ],
            'volume' => [
                'label' => 'Volume / Cairan',
                'color' => 'cyan',
            ],
            'area' => [
                'label' => 'Luas',
                'color' => 'indigo',
            ],
            'time' => [
                'label' => 'Waktu',
                'color' => 'purple',
            ],
            'other' => [
                'label' => 'Lainnya',
                'color' => 'gray',
            ],
        ];
    }
}
