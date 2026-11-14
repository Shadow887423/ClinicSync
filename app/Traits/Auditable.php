<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Boot the trait and hook into Eloquent model events
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $user = Auth::user();
            $newValues = $model->filterSensitiveAuditAttributes($model->getAttributes());
            // Omit timestamps from new_values to keep payload clean
            unset($newValues['created_at'], $newValues['updated_at'], $newValues['deleted_at']);

            $description = $model->getAuditRecordName() . ' was created';
            if (isset($newValues['status'])) {
                $description .= " with initial status '{$newValues['status']}'";
            }

            AuditLog::create([
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'action' => 'created',
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System / Guest',
                'user_role' => $user?->role ?? 'system',
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'System',
                'old_values' => null,
                'new_values' => $newValues,
                'description' => $description,
                'action_result' => 'success',
            ]);
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            unset($dirty['updated_at']);

            if (empty($dirty)) {
                return;
            }

            $user = Auth::user();
            $original = $model->getOriginal();

            $oldValues = [];
            $newValues = [];

            foreach ($dirty as $key => $newValue) {
                $oldValues[$key] = $original[$key] ?? null;
                $newValues[$key] = $newValue;
            }

            $oldFiltered = $model->filterSensitiveAuditAttributes($oldValues);
            $newFiltered = $model->filterSensitiveAuditAttributes($newValues);

            // Determine appropriate action
            $action = 'updated';
            if (count($dirty) === 1 && isset($dirty['status'])) {
                $action = 'status_changed';
                $description = "Status transitioned from '" . ($oldValues['status'] ?? 'none') . "' to '{$newValues['status']}'";
            } elseif (isset($dirty['dentist_id']) && !isset($oldValues['dentist_id'])) {
                $action = 'assigned';
                $description = "Assigned to dentist ID #" . $newValues['dentist_id'];
            } else {
                $changedFields = implode(', ', array_map(fn($f) => ucwords(str_replace('_', ' ', $f)), array_keys($dirty)));
                $description = "Modified fields: {$changedFields}";
            }

            AuditLog::create([
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'action' => $action,
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'System',
                'user_role' => $user?->role ?? 'system',
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'System',
                'old_values' => $oldFiltered,
                'new_values' => $newFiltered,
                'description' => $description,
                'action_result' => 'success',
            ]);
        });

        if (method_exists(static::class, 'deleted')) {
            static::deleted(function ($model) {
                // If soft deletes is in use, only log if model is soft-deleted
                $user = Auth::user();
                $reason = $model->deletion_reason ?? Request::input('reason') ?? Request::input('cancellation_reason') ?? null;

                AuditLog::create([
                    'auditable_type' => get_class($model),
                    'auditable_id' => $model->getKey(),
                    'action' => 'deleted',
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'System',
                    'user_role' => $user?->role ?? 'system',
                    'ip_address' => Request::ip() ?? '127.0.0.1',
                    'user_agent' => Request::userAgent() ?? 'System',
                    'old_values' => $model->filterSensitiveAuditAttributes($model->getAttributes()),
                    'new_values' => ['deleted_at' => now()->toDateTimeString()],
                    'description' => $model->getAuditRecordName() . ' moved to recoverable deleted state',
                    'reason' => $reason,
                    'action_result' => 'deleted',
                ]);
            });
        }

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $user = Auth::user();
                $reason = Request::input('restoration_reason') ?? Request::input('reason') ?? 'Restored by administrator';

                AuditLog::create([
                    'auditable_type' => get_class($model),
                    'auditable_id' => $model->getKey(),
                    'action' => 'restored',
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'System Administrator',
                    'user_role' => $user?->role ?? 'admin',
                    'ip_address' => Request::ip() ?? '127.0.0.1',
                    'user_agent' => Request::userAgent() ?? 'System',
                    'old_values' => ['deleted_at' => $model->getOriginal('deleted_at')],
                    'new_values' => ['deleted_at' => null],
                    'description' => $model->getAuditRecordName() . ' successfully restored to active state',
                    'reason' => $reason,
                    'action_result' => 'restored',
                ]);
            });
        }
    }

    /**
     * Relationship to audit log entries
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('created_at')->latest('id');
    }

    /**
     * Log a manual or custom audit event on this model
     */
    public function logAuditEvent(
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?User $user = null
    ): AuditLog {
        $actor = $user ?? Auth::user();

        return AuditLog::create([
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'action' => $action,
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? 'System',
            'user_role' => $actor?->role ?? 'system',
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'System',
            'old_values' => $oldValues ? $this->filterSensitiveAuditAttributes($oldValues) : null,
            'new_values' => $newValues ? $this->filterSensitiveAuditAttributes($newValues) : null,
            'description' => $description,
            'reason' => $reason,
            'action_result' => 'success',
        ]);
    }

    /**
     * Redact passwords and security sensitive keys from audit snapshots
     */
    public function filterSensitiveAuditAttributes(array $attributes): array
    {
        $sensitiveKeys = [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'api_token',
            'secret',
            'auth_token',
        ];

        foreach ($attributes as $key => $value) {
            $lowerKey = strtolower($key);
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $attributes[$key] = '[REDACTED]';
                    break;
                }
            }
        }

        return $attributes;
    }

    /**
     * Label identifier for audit descriptions
     */
    public function getAuditRecordName(): string
    {
        $base = class_basename($this);
        $identifier = $this->appointment_number ?? $this->name ?? "#{$this->getKey()}";
        return "{$base} ({$identifier})";
    }
}
