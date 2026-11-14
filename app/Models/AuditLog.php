<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'user_id',
        'user_name',
        'user_role',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'description',
        'reason',
        'action_result',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Polymorphic target model (supports soft-deleted records)
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * User who performed the action (nullable for system events)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * Get computed field-by-field differences for before-and-after comparisons
     */
    public function getDiffAttribute(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $diffs = [];

        foreach ($allKeys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            // Normalize display representation
            if (is_bool($oldVal)) {
                $oldVal = $oldVal ? 'Yes' : 'No';
            } elseif (is_array($oldVal)) {
                $oldVal = json_encode($oldVal, JSON_PRETTY_PRINT);
            }

            if (is_bool($newVal)) {
                $newVal = $newVal ? 'Yes' : 'No';
            } elseif (is_array($newVal)) {
                $newVal = json_encode($newVal, JSON_PRETTY_PRINT);
            }

            $diffs[] = [
                'field' => $key,
                'label' => ucwords(str_replace('_', ' ', $key)),
                'old' => $oldVal,
                'new' => $newVal,
                'changed' => $oldVal !== $newVal,
            ];
        }

        return $diffs;
    }

    /**
     * Human-friendly label for auditable type
     */
    public function getRecordTypeLabelAttribute(): string
    {
        $base = class_basename($this->auditable_type);
        return match ($base) {
            'Appointment' => 'Appointment',
            'User' => 'User / Patient',
            'Dentist' => 'Dentist',
            'TreatmentRecord' => 'Treatment Record',
            'Attachment' => 'Attachment',
            'Dependent' => 'Dependent',
            'PatientMedicalHistory' => 'Medical History',
            default => $base,
        };
    }

    /**
     * CSS badge class for action
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action) {
            'created' => 'bg-success',
            'updated' => 'bg-primary',
            'status_changed' => 'bg-warning text-dark',
            'assigned' => 'bg-info text-dark',
            'deleted' => 'bg-danger',
            'restored' => 'bg-teal text-white',
            default => 'bg-secondary',
        };
    }

    /**
     * Bootstrap icon for action
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created' => 'bi-plus-circle-fill text-success',
            'updated' => 'bi-pencil-square text-primary',
            'status_changed' => 'bi-arrow-left-right text-warning',
            'assigned' => 'bi-person-check-fill text-info',
            'deleted' => 'bi-trash3-fill text-danger',
            'restored' => 'bi-arrow-counterclockwise text-teal',
            default => 'bi-clock-history text-secondary',
        };
    }

    /**
     * Scopes
     */
    public function scopeForRecord($query, string $type, int $id)
    {
        // Support class name or short alias
        if (!str_contains($type, '\\')) {
            $type = 'App\\Models\\' . ucfirst($type);
        }
        return $query->where('auditable_type', $type)->where('auditable_id', $id);
    }

    public function scopeByAction($query, ?string $action)
    {
        if ($action && $action !== 'all') {
            return $query->where('action', $action);
        }
        return $query;
    }

    public function scopeByRecordType($query, ?string $type)
    {
        if ($type && $type !== 'all') {
            if (!str_contains($type, '\\')) {
                $type = 'App\\Models\\' . ucfirst($type);
            }
            return $query->where('auditable_type', $type);
        }
        return $query;
    }

    public function scopeByUser($query, $userId)
    {
        if ($userId && $userId !== 'all') {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    public function scopeInDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
              ->orWhere('reason', 'like', "%{$term}%")
              ->orWhere('user_name', 'like', "%{$term}%")
              ->orWhere('auditable_id', 'like', "%{$term}%")
              ->orWhere('ip_address', 'like', "%{$term}%");
        });
    }
}
