<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Centralized Audit Log & History Hub
     */
    public function index(Request $request)
    {
        $filters = [
            'record_type' => $request->query('record_type', 'all'),
            'record_id' => $request->query('record_id'),
            'action' => $request->query('action', 'all'),
            'user_id' => $request->query('user_id', 'all'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'search' => $request->query('search'),
        ];

        $logs = $this->auditService->searchLogs($filters, 20);
        $types = $this->auditService->getSupportedTypes();
        $users = User::select('id', 'name', 'role')->orderBy('name')->get();

        // Summary KPI statistics
        $stats = [
            'total_events' => AuditLog::count(),
            'today_events' => AuditLog::whereDate('created_at', today())->count(),
            'status_changes' => AuditLog::where('action', 'status_changed')->count(),
            'deletions' => AuditLog::where('action', 'deleted')->count(),
            'restorations' => AuditLog::where('action', 'restored')->count(),
        ];

        return view('staff.audit.index', compact('logs', 'filters', 'types', 'users', 'stats'));
    }

    /**
     * Complete lifecycle history and timeline for an individual record
     */
    public function recordHistory(string $type, int $id)
    {
        $record = $this->auditService->findRecord($type, $id);
        if (!$record) {
            return redirect()->route('staff.audit.index')->with('error', 'Record not found or has been permanently purged.');
        }

        $timeline = $this->auditService->getRecordTimeline($type, $id);
        $types = $this->auditService->getSupportedTypes();
        $typeMeta = $types[$this->auditService->resolveTypeAlias(get_class($record))] ?? [
            'label' => class_basename($record),
            'icon' => 'bi-file-text'
        ];

        return view('staff.audit.record-history', [
            'record' => $record,
            'type' => $type,
            'typeLabel' => $typeMeta['label'],
            'typeIcon' => $typeMeta['icon'],
            'timeline' => $timeline,
            'recordIdentifier' => $record->appointment_number ?? $record->name ?? "#{$record->id}",
        ]);
    }

    /**
     * AJAX/Modal diff comparison for an individual audit event
     */
    public function compare(AuditLog $auditLog)
    {
        return response()->json([
            'id' => $auditLog->id,
            'action' => $auditLog->action,
            'action_label' => ucfirst(str_replace('_', ' ', $auditLog->action)),
            'description' => $auditLog->description,
            'user' => $auditLog->user_name ?? 'System',
            'created_at' => $auditLog->created_at->format('M d, Y \a\t g:i:s A'),
            'diff' => $auditLog->diff,
            'reason' => $auditLog->reason,
        ]);
    }

    /**
     * Deleted Records & Trash Management Console
     */
    public function deletedRecords(Request $request)
    {
        $filters = [
            'record_type' => $request->query('record_type', 'all'),
        ];

        $deletedRecords = $this->auditService->getDeletedRecords($filters);
        $types = $this->auditService->getSupportedTypes();

        return view('staff.audit.deleted', compact('deletedRecords', 'types', 'filters'));
    }

    /**
     * Restore a soft-deleted record with reason and audit trail
     */
    public function restore(Request $request, string $type, int $id)
    {
        $request->validate([
            'restoration_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $request->input('restoration_reason') ?: 'Restored by Administrator (' . Auth::user()->name . ')';
        $success = $this->auditService->restoreRecord($type, $id, $reason, Auth::user());

        if (!$success) {
            return back()->with('error', 'Unable to restore record. It may not exist or does not support restoration.');
        }

        return back()->with('success', "Record #{$id} restored successfully to active state with audit log appended.");
    }

    /**
     * Soft delete an individual record with reason
     */
    public function deleteRecord(Request $request, string $type, int $id)
    {
        $request->validate([
            'deletion_reason' => ['required', 'string', 'max:500'],
        ]);

        $success = $this->auditService->deleteRecord($type, $id, $request->input('deletion_reason'), Auth::user());

        if (!$success) {
            return back()->with('error', 'Unable to delete record.');
        }

        return back()->with('info', "Record #{$id} moved to recoverable trash with full lifecycle audit trail preserved.");
    }

    /**
     * Export audit logs to CSV
     */
    public function export(Request $request)
    {
        if ($request->has('record_type') && $request->has('record_id') && $request->record_id) {
            $logs = $this->auditService->getRecordTimeline($request->record_type, (int) $request->record_id);
        } else {
            $filters = [
                'record_type' => $request->query('record_type', 'all'),
                'record_id' => $request->query('record_id'),
                'action' => $request->query('action', 'all'),
                'user_id' => $request->query('user_id', 'all'),
                'from_date' => $request->query('from_date'),
                'to_date' => $request->query('to_date'),
                'search' => $request->query('search'),
            ];
            $logs = $this->auditService->searchLogs($filters, 1000)->items();
        }

        return $this->auditService->exportCsv($logs);
    }
}
