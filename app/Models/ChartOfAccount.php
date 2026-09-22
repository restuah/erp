<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_code',
        'account_name',
        'level',
        'parent_code',
        'parent_id',
        'jenis',
        'kategori',
        'postable',
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
            'level' => 'integer',
            'postable' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Akun Induk (Parent).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    /**
     * Sub-akun langsung (Direct Children).
     */
    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id')->orderBy('account_code');
    }

    /**
     * Sub-akun rekursif (Nested Children).
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * User pembuat.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User pengubah terakhir.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Dapatkan semua ID keturunan (descendants) untuk mencegah circular hierarchy.
     *
     * @return array<string>
     */
    public function getAllDescendantIds(): array
    {
        $descendantIds = [];
        $queue = $this->children()->pluck('id')->all();

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            $descendantIds[] = $currentId;
            $childIds = ChartOfAccount::where('parent_id', $currentId)->pluck('id')->all();
            foreach ($childIds as $cId) {
                $queue[] = $cId;
            }
        }

        return $descendantIds;
    }

    /**
     * Sinkronisasi status postable untuk akun ini berdasarkan apakah memiliki child atau tidak.
     */
    public function syncPostableStatus(): void
    {
        $hasChildren = $this->children()->exists();
        if ($hasChildren && $this->postable) {
            $this->updateQuietly(['postable' => false]);
        }
    }
}
