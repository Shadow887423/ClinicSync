<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\TreatmentRecord;
use App\Models\Attachment;
use App\Models\Dependent;
use App\Models\User;
use App\Models\PatientMedicalHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditService
{
    /**
     * Map of supported auditable types and their classes
     */
    public const TYPE_MAP = [
        'appointment' => Appointment::class,
        'user' => User::class,
        'dentist' => Dentist::class,
        'treatment_record' => TreatmentRecord::class,
        'attachment' => Attachment::class,
        'dependent' => Dependent::class,
        'medical_history' => PatientMedicalHistory::class,
    ];

    /**
     * Get all supported auditable types with human-readable labels
     */
    public function getSupportedTypes(): array
    {
        return [
            'appointment' => ['class' => Appointment::class, 'label' => 'Appointments', 'icon' => 'bi-calendar-check'],
            'user' => ['class' => User::class, 'label' => 'Users / Patients', 'icon' => 'bi-person'],
            'dentist' => ['class' => Dentist::class, 'label' => 'Dentists', 'icon' => 'bi-person-badge'],
            'treatment_record' => ['class' => TreatmentRecord::class, 'label' => 'Treatment Records', 'icon' => 'bi-file-earmark-medical'],
            'attachment' => ['class' => Attachment::class, 'label' => 'Attachments', 'icon' => 'bi-paperclip'],
            'dependent' => ['class' => Dependent::class, 'label' => 'Dependents', 'icon' => 'bi-people'],
        ];
    }

    /**
     * Resolve class string from type string or alias
     */
    public function resolveClass(string $type): ?string
    {
        $normalized = strtolower(trim($type));
        if (isset(self::TYPE_MAP[$normalized])) {
            return self::TYPE_MAP[$normalized];
        }

        if (class_exists($type)) {
            return $type;
        }

        $namespaced = 'App\\Models\\' . ucfirst($type);
        if (class_exists($namespaced)) {
            return $namespaced;
        }

        return null;
    }

    /**
     * Resolve short alias from class name
     */
    public function resolveTypeAlias(string $className): string
    {
        $base = class_basename($className);
        return match ($base) {
            'Appointment' => 'appointment',
            'User' => 'user',
            'Dentist' => 'dentist',
            'TreatmentRecord' => 'treatment_record',
            'Attachment' => 'attachment',
            'Dependent' => 'dependent',
            'PatientMedicalHistory' => 'medical_history',
            default => strtolower($base),
        };
    }

    /**
     * Find a record across active and soft-deleted states
     */
    public function findRecord(string $type, int $id): ?Model
    {
        $class = $this->resolveClass($type);
        if (!$class || !class_exists($class)) {
            return null;
        }

        $query = $class::query();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class))) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    /**
     * Search and filter centralized audit logs
     */
    public function searchLogs(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user');

        if (!empty($filters['record_type']) && $filters['record_type'] !== 'all') {
            $class = $this->resolveClass($filters['record_type']);
            if ($class) {
                $query->where('auditable_type', $class);
            }
        }

        if (!empty($filters['record_id'])) {
            $query->where('auditable_id', $filters['record_id']);
        }

        if (!empty($filters['action']) && $filters['action'] !== 'all') {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['user_id']) && $filters['user_id'] !== 'all') {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                  ->orWhere('reason', 'like', "%{$term}%")
                  ->orWhere('user_name', 'like', "%{$term}%")
                  ->orWhere('auditable_id', 'like', "%{$term}%")
                  ->orWhere('ip_address', 'like', "%{$term}%");
            });
        }

        return $query->latest('created_at')->latest('id')->paginate($perPage);
    }

    /**
     * Retrieve full chronological timeline for an individual record
     */
    public function getRecordTimeline(string $type, int $id)
    {
        $class = $this->resolveClass($type);
        if (!$class) {
            return collect();
        }

        return AuditLog::where('auditable_type', $class)
            ->where('auditable_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Soft delete a record with reason and audit trail
     */
    public function deleteRecord(string $type, int $id, ?string $reason, ?User $admin): bool
    {
        $record = $this->findRecord($type, $id);
        if (!$record) {
            return false;
        }

        if (property_exists($record, 'deletion_reason') || in_array('deletion_reason', $record->getFillable())) {
            $record->deletion_reason = $reason;
            $record->saveQuietly();
        }

        return (bool) $record->delete();
    }

    /**
     * Restore a soft-deleted record with reason and audit trail
     */
    public function restoreRecord(string $type, int $id, ?string $reason, ?User $admin): bool
    {
        $record = $this->findRecord($type, $id);
        if (!$record || !method_exists($record, 'restore')) {
            return false;
        }

        if (method_exists($record, 'trashed') && !$record->trashed()) {
            return true; // already active
        }

        // Clear deletion reason if field exists
        if (property_exists($record, 'deletion_reason') || in_array('deletion_reason', $record->getFillable())) {
            $record->deletion_reason = null;
        }

        $restored = $record->restore();

        // If the model is an appointment or user, ensure active status
        if ($restored) {
            if ($record instanceof User && $record->status === 'deactivated') {
                $record->status = 'active';
                $record->save();
            }
        }

        return (bool) $restored;
    }

    /**
     * Get all soft-deleted records across auditable models
     */
    public function getDeletedRecords(array $filters = [], int $perPage = 25): array
    {
        $deletedRecords = [];

        foreach ($this->getSupportedTypes() as $key => $meta) {
            $class = $meta['class'];
            if (!in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class))) {
                continue;
            }

            if (!empty($filters['record_type']) && $filters['record_type'] !== 'all' && $filters['record_type'] !== $key) {
                continue;
            }

            $items = $class::onlyTrashed()->latest('deleted_at')->take(50)->get();
            foreach ($items as $item) {
                $deletedRecords[] = [
                    'type_key' => $key,
                    'type_label' => $meta['label'],
                    'id' => $item->id,
                    'display_name' => $item->appointment_number ?? $item->name ?? "#{$item->id}",
                    'deleted_at' => $item->deleted_at,
                    'deletion_reason' => $item->deletion_reason ?? 'No reason recorded',
                    'record' => $item,
                ];
            }
        }

        // Sort by deleted_at descending
        usort($deletedRecords, function ($a, $b) {
            return strtotime($b['deleted_at']) - strtotime($a['deleted_at']);
        });

        return $deletedRecords;
    }

    /**
     * Generate CSV export stream of audit logs
     */
    public function exportCsv($logs): StreamedResponse
    {
        $filename = 'clinicsync_audit_history_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            // CSV Columns
            fputcsv($handle, [
                'Event ID',
                'Timestamp (UTC)',
                'Record Type',
                'Record ID',
                'Action',
                'Result',
                'User Responsible',
                'User Role',
                'IP Address',
                'Event Description',
                'Reason / Notes',
                'Previous Values (JSON)',
                'New Values (JSON)',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toIso8601String() ?? '',
                    $log->record_type_label,
                    $log->auditable_id,
                    ucfirst(str_replace('_', ' ', $log->action)),
                    ucfirst($log->action_result),
                    $log->user_name ?? 'System',
                    $log->user_role ?? 'system',
                    $log->ip_address ?? '',
                    $log->description ?? '',
                    $log->reason ?? '',
                    $log->old_values ? json_encode($log->old_values) : '',
                    $log->new_values ? json_encode($log->new_values) : '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
