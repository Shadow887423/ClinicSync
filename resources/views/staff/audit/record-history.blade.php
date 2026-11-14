@extends('layouts.dashboard')

@section('title', "Lifecycle History — {$typeLabel} {$recordIdentifier}")
@section('page-title', "Record-Level Audit History")

@section('content')
<div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <a href="{{ route('staff.audit.index') }}" class="text-decoration-none small text-muted me-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Audit Hub
        </a>
        @if($type === 'appointment' && $record)
            <a href="{{ route('staff.appointments.show', $record->id) }}" class="text-decoration-none small text-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i> Open Appointment Console
            </a>
        @endif
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('staff.audit.export', ['record_type' => $type, 'record_id' => $record->id]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-download me-1"></i> Export Record CSV
        </a>

        @if(method_exists($record, 'trashed') && $record->trashed())
            <button type="button" class="btn btn-sm btn-teal rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#restoreRecordModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Record
            </button>
        @else
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#deleteRecordModal">
                <i class="bi bi-trash3 me-1"></i> Soft Delete Record
            </button>
        @endif
    </div>
</div>

<!-- Record Overview Header Card -->
<div class="card card-custom p-4 mb-4 border shadow-sm">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                <i class="bi {{ $typeIcon }} fs-3"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-light text-dark border">{{ $typeLabel }}</span>
                    @if(method_exists($record, 'trashed') && $record->trashed())
                        <span class="badge bg-danger">SOFT DELETED</span>
                    @elseif(isset($record->status))
                        <span class="badge bg-info text-dark">Status: {{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                    @endif
                </div>
                <h4 class="fw-bold text-dark mb-0 mt-1"><code>{{ $recordIdentifier }}</code></h4>
                <div class="text-muted small">Internal Database ID: #{{ $record->id }}</div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-4 text-muted small border-start-md ps-md-4">
            <div>
                <div class="text-uppercase fw-bold" style="font-size: 0.7rem;">Initial Creation</div>
                <div class="fw-semibold text-dark">{{ $record->created_at ? $record->created_at->format('M d, Y g:i A') : 'Unknown' }}</div>
            </div>
            <div>
                <div class="text-uppercase fw-bold" style="font-size: 0.7rem;">Last Modified</div>
                <div class="fw-semibold text-dark">{{ $record->updated_at ? $record->updated_at->format('M d, Y g:i A') : 'Never' }}</div>
            </div>
            <div>
                <div class="text-uppercase fw-bold" style="font-size: 0.7rem;">Lifecycle Events</div>
                <div class="fw-bold text-primary fs-6">{{ count($timeline) }} Recorded</div>
            </div>
        </div>
    </div>

    @if(method_exists($record, 'trashed') && $record->trashed() && $record->deletion_reason)
        <div class="alert alert-danger mt-3 mb-0 py-2 px-3 small border-start border-4 border-danger">
            <div class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Deletion Reason on Record:</div>
            <div>{{ $record->deletion_reason }}</div>
        </div>
    @endif
</div>

<!-- Interactive Chronological Lifecycle Timeline -->
<div class="card card-custom p-4 mb-4 border shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-clock-history text-primary me-2"></i> Chronological Lifecycle Timeline
            </h5>
            <p class="text-muted small mb-0">Complete provenance from initial creation to current state with before-and-after diff inspection.</p>
        </div>
        <span class="badge bg-light text-muted border">Tamper-Proof Audit Record</span>
    </div>

    @include('partials.audit-timeline', ['timeline' => $timeline])
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.audit.restore', ['type' => $type, 'id' => $record->id]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-arrow-counterclockwise text-teal me-1"></i> Restore Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Restoring this <strong>{{ $typeLabel }} (#{{ $record->id }})</strong> will bring it back to active state and preserve both pre-deletion and restoration history.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Restoration Justification / Reason</label>
                        <textarea name="restoration_reason" class="form-control" rows="3" placeholder="Provide reason for restoring this record..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">Confirm & Restore</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Soft Delete Modal -->
<div class="modal fade" id="deleteRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.audit.delete', ['type' => $type, 'id' => $record->id]) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash3 me-1"></i> Move Record to Trash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Moving this record to trash will perform a <strong>recoverable soft delete</strong>. The record's ID, past modification history, and relationships remain fully preserved.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-danger">Deletion Reason <span class="text-danger">*</span></label>
                        <textarea name="deletion_reason" class="form-control" rows="3" placeholder="Explain why this record is being moved to trash..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Move to Trash</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
