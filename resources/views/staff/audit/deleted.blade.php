@extends('layouts.dashboard')

@section('title', 'Deleted Records & Trash Management')
@section('page-title', 'Deleted Records Console')

@section('content')
<div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <a href="{{ route('staff.audit.index') }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Audit Hub
        </a>
    </div>
</div>

<div class="card card-custom p-4 mb-4 border shadow-sm">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <div>
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-trash3 text-danger me-2"></i> Recoverable Deleted Records (Trash)
            </h5>
            <p class="text-muted small mb-0">
                Inspect deleted records across all application modules. Soft-deleted records retain their original identifiers, historical audit logs, and can be restored with full audit tracking.
            </p>
        </div>
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">
            {{ count($deletedRecords) }} Recoverable Record(s)
        </span>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('staff.audit.deleted') }}" class="mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label small fw-bold text-muted mb-0">Filter by Type:</label>
            </div>
            <div class="col-auto">
                <select name="record_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Modules</option>
                    @foreach($types as $key => $meta)
                        <option value="{{ $key }}" {{ ($filters['record_type'] ?? '') === $key ? 'selected' : '' }}>
                            {{ $meta['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(!empty($filters['record_type']) && $filters['record_type'] !== 'all')
                <div class="col-auto">
                    <a href="{{ route('staff.audit.deleted') }}" class="btn btn-sm btn-light border rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>
                </div>
            @endif
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th style="width: 160px;">Record Type</th>
                    <th style="width: 200px;">Identifier / ID</th>
                    <th style="width: 180px;">Deletion Timestamp</th>
                    <th>Recorded Deletion Reason</th>
                    <th style="width: 220px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deletedRecords as $item)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $item['type_label'] }}</span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $item['display_name'] }}</div>
                            <div class="text-muted small">Record ID: #{{ $item['id'] }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-danger small">
                                <i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($item['deleted_at'])->format('M d, Y') }}
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ \Carbon\Carbon::parse($item['deleted_at'])->format('g:i:s A') }}
                                ({{ \Carbon\Carbon::parse($item['deleted_at'])->diffForHumans() }})
                            </div>
                        </td>
                        <td>
                            <div class="p-2 bg-light rounded text-dark small border">
                                <i class="bi bi-chat-left-quote me-1 text-muted"></i>
                                {{ $item['deletion_reason'] }}
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                                <a href="{{ route('staff.audit.record', ['type' => $item['type_key'], 'id' => $item['id']]) }}" 
                                   class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                   title="Inspect complete lifecycle and prior edits">
                                    <i class="bi bi-clock-history me-1"></i> History
                                </a>

                                <button type="button" 
                                        class="btn btn-sm btn-teal rounded-pill px-3"
                                        onclick="openRestoreModal('{{ $item['type_key'] }}', {{ $item['id'] }}, '{{ addslashes($item['display_name']) }}')">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                            Trash is empty. There are currently no soft-deleted records in this category.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Restoring Record -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="restoreForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-arrow-counterclockwise text-teal me-1"></i> Confirm Record Restoration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        You are about to restore <strong id="restoreTargetName" class="text-dark"></strong>.
                        Its original state will be restored, and an audit restoration log will be appended to its history.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Restoration Reason / Justification</label>
                        <textarea name="restoration_reason" class="form-control" rows="3" placeholder="Enter reason for restoring this record..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">Restore Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openRestoreModal(type, id, displayName) {
    const form = document.getElementById('restoreForm');
    form.action = '{{ url("staff/audit/restore") }}/' + type + '/' + id;
    document.getElementById('restoreTargetName').textContent = displayName + ' (#' + id + ')';
    const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}
</script>
@endsection
