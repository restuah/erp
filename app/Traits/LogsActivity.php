<?php

namespace App\Traits;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Boot the trait to hook into Eloquent lifecycle events.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            $model->recordActivityLog('created');
        });

        static::updated(function (Model $model) {
            $model->recordActivityLog('updated');
        });

        static::deleted(function (Model $model) {
            $isForceDelete = method_exists($model, 'isForceDeleting') ? $model->isForceDeleting() : false;
            $event = $isForceDelete ? 'force_deleted' : 'deleted';
            $model->recordActivityLog($event);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                $model->recordActivityLog('restored');
            });
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function (Model $model) {
                $model->recordActivityLog('force_deleted');
            });
        }
    }

    /**
     * Record the activity log for this model.
     */
    public function recordActivityLog(string $event): void
    {
        $logName = $this->getActivityLogName();
        $recordTitle = $this->getActivityRecordTitle();
        $subjectLabel = $this->getActivitySubjectLabel();
        $properties = [];

        $description = match ($event) {
            'created' => "Menambahkan {$recordTitle} baru: {$subjectLabel}",
            'updated' => "Memperbarui {$recordTitle}: {$subjectLabel}",
            'deleted' => "Menghapus {$recordTitle}: {$subjectLabel}",
            'restored' => "Memulihkan {$recordTitle}: {$subjectLabel} dari tempat sampah",
            'force_deleted' => "Menghapus permanen {$recordTitle}: {$subjectLabel}",
            default => "Aktivitas {$event} pada {$recordTitle}: {$subjectLabel}",
        };

        if ($event === 'created') {
            $attributes = $this->filterLogAttributes($this->getAttributes());
            $properties = [
                'attributes' => $attributes,
            ];
        } elseif ($event === 'updated') {
            $dirty = $this->getDirty();
            $excluded = $this->getActivityExcludedAttributes();

            $changedKeys = array_diff(array_keys($dirty), $excluded);

            if (empty($changedKeys)) {
                return; // Nothing significant changed
            }

            $oldAttributes = [];
            $newAttributes = [];

            foreach ($changedKeys as $key) {
                $oldAttributes[$key] = $this->getOriginal($key);
                $newAttributes[$key] = $this->getAttribute($key);
            }

            $properties = [
                'old' => $this->filterLogAttributes($oldAttributes),
                'attributes' => $this->filterLogAttributes($newAttributes),
            ];
        } elseif (in_array($event, ['deleted', 'force_deleted'])) {
            $properties = [
                'attributes' => $this->filterLogAttributes($this->getAttributes()),
            ];
        }

        ActivityLogger::log(
            description: $description,
            event: $event,
            logName: $logName,
            subject: $this,
            properties: $properties,
            status: in_array($event, ['deleted', 'force_deleted']) ? 'warning' : 'success'
        );
    }

    /**
     * Get module / log name for this model.
     */
    public function getActivityLogName(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Get human-readable Indonesian title for this model.
     */
    public function getActivityRecordTitle(): string
    {
        return match (class_basename($this)) {
            'User' => 'pengguna',
            'Role' => 'peran',
            'Permission' => 'izin sistem',
            default => strtolower(class_basename($this)),
        };
    }

    /**
     * Get subject identifier label (e.g. name or email).
     */
    public function getActivitySubjectLabel(): string
    {
        if (isset($this->name) && isset($this->email)) {
            return "{$this->name} ({$this->email})";
        }

        if (isset($this->name)) {
            return (string) $this->name;
        }

        if (isset($this->email)) {
            return (string) $this->email;
        }

        return class_basename($this).' #'.$this->getKey();
    }

    /**
     * Attributes that should not trigger or be recorded in activity logs.
     */
    public function getActivityExcludedAttributes(): array
    {
        return [
            'updated_at',
            'remember_token',
            'password',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];
    }

    /**
     * Filter out excluded attributes from array.
     */
    protected function filterLogAttributes(array $attributes): array
    {
        $excluded = $this->getActivityExcludedAttributes();

        return array_filter($attributes, function ($key) use ($excluded) {
            return ! in_array($key, $excluded, true);
        }, ARRAY_FILTER_USE_KEY);
    }
}
