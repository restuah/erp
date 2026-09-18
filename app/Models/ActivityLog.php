<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'event',
        'causer_type',
        'causer_id',
        'causer_name',
        'causer_email',
        'causer_role',
        'properties',
        'method',
        'url',
        'ip_address',
        'user_agent',
        'device',
        'status',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The user / causer who initiated this action.
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id')->withTrashed();
    }

    /**
     * The subject model affected by this activity.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope query to search records.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhere('causer_name', 'like', "%{$search}%")
                ->orWhere('causer_email', 'like', "%{$search}%")
                ->orWhere('subject_label', 'like', "%{$search}%")
                ->orWhere('ip_address', 'like', "%{$search}%")
                ->orWhere('event', 'like', "%{$search}%")
                ->orWhere('log_name', 'like', "%{$search}%");
        });
    }

    /**
     * Scope query by event/action.
     */
    public function scopeFilterByEvent(Builder $query, ?string $event): Builder
    {
        if (empty($event)) {
            return $query;
        }

        return $query->where('event', $event);
    }

    /**
     * Scope query by module/log_name.
     */
    public function scopeFilterByModule(Builder $query, ?string $module): Builder
    {
        if (empty($module)) {
            return $query;
        }

        return $query->where('log_name', $module);
    }

    /**
     * Scope query by causer user id.
     */
    public function scopeFilterByCauser(Builder $query, ?string $causerId): Builder
    {
        if (empty($causerId)) {
            return $query;
        }

        return $query->where('causer_id', $causerId);
    }

    /**
     * Scope query by date range.
     */
    public function scopeFilterByDateRange(
        Builder $query,
        ?string $range,
        ?string $startDate = null,
        ?string $endDate = null
    ): Builder {
        if (empty($range) && empty($startDate) && empty($endDate)) {
            return $query;
        }

        return match ($range) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'yesterday' => $query->whereDate('created_at', Carbon::yesterday()),
            '7_days' => $query->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay()),
            '30_days' => $query->where('created_at', '>=', Carbon::now()->subDays(30)->startOfDay()),
            'this_month' => $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]),
            'custom' => $query->when($startDate, fn ($q) => $q->whereDate('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('created_at', '<=', $endDate)),
            default => $query,
        };
    }
}
