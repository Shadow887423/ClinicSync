@extends('layouts.dashboard')

@section('title', 'Centralized Audit History & Complete Record Lifecycle')
@section('page-title', 'Record-Level Audit Trail')

@section('content')
<!-- KPI Header Statistics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="card stat-card p-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Total Lifecycle Events</span>
                <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['total_events']) }}</div>
            <div class="small text-muted">Complete audit trail</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card p-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Events Today</span>
                <div class="stat-icon-wrapper bg-teal bg-opacity-10 text-teal">
                    <i class="bi bi-calendar2-day"></i>
                </div>
            </div>
            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['today_events']) }}</div>
            <div class="small text-muted">Today's modifications</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card p-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Status Transitions</span>
                <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['status_changes']) }}</div>
            <div class="small text-muted">Triage & workflow transitions</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card p-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Deletions (Trash)</span>
                <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-trash3"></i>
                </div>
            </div>
            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['deletions']) }}</div>
            <div class="small text-muted">Recoverable soft deletes</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="card stat-card p-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-muted small fw-semibold">Restored Records</span>
                <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
            </div>
            <div class="fs-4 fw-bold text-dark">{{ number_format($stats['restorations']) }}</div>
            <div class="small text-muted">Admin restorations</div>
        </div>
    </div>
</div>

<!-- Search & Filtering Toolbar -->
<div class="card card-custom p-4 mb-4 border shadow-sm">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h5 class="fw-bold text-dark mb-0">
            <i class="bi bi-funnel text-primary me-2"></i> Audit History Search & Filters
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.audit.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-download me-1"></i> Export Filtered CSV
            </a>
            <a href="{{ route('staff.audit.deleted') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                <i class="bi bi-trash3 me-1"></i> View Deleted Records
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('staff.audit.index') }}">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Record Type</label>
                <select name="record_type" class="form-select form-select-sm">
                    <option value="all">All Record Types</option>
                    @foreach($types as $key => $meta)
                        <option value="{{ $key }}" {{ ($filters['record_type'] ?? '') === $key ? 'selected' : '' }}>
                            {{ $meta['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Record ID</label>
                <input type="number" name="record_id" class="form-control form-select-sm" placeholder="e.g. 1024" value="{{ $filters['record_id'] ?? '' }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Action Type</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="all">All Actions</option>
                    <option value="created" {{ ($filters['action'] ?? '') === 'created' ? 'selected' : '' }}>Created</option>
                    <option value="updated" {{ ($filters['action'] ?? '') === 'updated' ? 'selected' : '' }}>Updated</option>
                    <option value="status_changed" {{ ($filters['action'] ?? '') === 'status_changed' ? 'selected' : '' }}>Status Changed</option>
                    <option value="assigned" {{ ($filters['action'] ?? '') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="deleted" {{ ($filters['action'] ?? '') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                    <option value="restored" {{ ($filters['action'] ?? '') === 'restored' ? 'selected' : '' }}>Restored</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Responsible User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="all">All Users / System</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ ucfirst($user->role) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Keyword Search</label>
                <input type="text" name="search" class="form-control form-select-sm" placeholder="Search description, reason, IP..." value="{{ $filters['search'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">From Date</label>
                <input type="date" name="from_date" class="form-control form-select-sm" value="{{ $filters['from_date'] ?? '' }}">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">To Date</label>
                <input type="date" name="to_date" class="form-control form-select-sm" value="{{ $filters['to_date'] ?? '' }}">
            </div>

            <div class="col-md-6 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">
                    <i class="bi bi-search me-1"></i> Apply Filters
                </button>
                <a href="{{ route('staff.audit.index') }}" class="btn btn-sm btn-light border rounded-pill px-3">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Centralized Audit Table -->
<div class="card card-custom p-4 border shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <i class="bi bi-list-check text-primary me-2"></i> Audit Activity Trail
        </h5>
        <span class="text-muted small">Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} events</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th style="width: 80px;">Event #</th>
                    <th style="width: 170px;">Timestamp</th>
                    <th style="width: 150px;">Target Record</th>
                    <th style="width: 120px;">Action</th>
                    <th style="width: 160px;">Responsible</th>
                    <th>Description & Changes</th>
                    <th style="width: 110px;">IP Address</th>
                    <th style="width: 100px;" class="text-end">Lifecycle</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $alias = app(\App\Services\AuditService::class)->resolveTypeAlias($log->auditable_type);
                    @endphp
                    <tr>
                        <td>
                            <code class="fw-bold text-secondary">#{{ $log->id }}</code>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small">{{ $log->created_at->format('M d, Y') }}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at->format('g:i:s A') }}</div>
                        </td>
                        <td>
                            <div>
                                <span class="badge bg-light text-dark border">{{ $log->record_type_label }}</span>
                                <div class="fw-bold text-dark small mt-1">ID: #{{ $log->auditable_id }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $log->action_badge_class }} text-uppercase" style="font-size: 0.7rem;">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark small">{{ $log->user_name ?? 'System' }}</div>
                            <span class="badge bg-secondary" style="font-size: 0.65rem;">{{ strtoupper($log->user_role ?? 'system') }}</span>
                        </td>
                        <td>
                            <div class="text-dark small fw-semibold">{{ $log->description }}</div>
                            @if($log->reason)
                                <div class="text-muted small fst-italic">Reason: {{ Str::limit($log->reason, 80) }}</div>
                            @endif

                            @if(!empty($log->diff))
                                <div class="mt-1">
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill small" 
                                            onclick="openDiffModal({{ $log->id }})">
                                        <i class="bi bi-layout-split me-1"></i> View Diff ({{ count($log->diff) }})
                                    </button>
                                </div>
                            @endif
                        </td>
                        <td>
                            <code class="small text-muted">{{ $log->ip_address ?: '127.0.0.1' }}</code>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('staff.audit.record', ['type' => $alias, 'id' => $log->auditable_id]) }}" 
                               class="btn btn-sm btn-light border rounded-pill px-2" 
                               title="Inspect complete record lifecycle">
                                <i class="bi bi-clock-history text-primary"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No audit records found matching the specified search criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-between align-items-center">
        <div>
            {{ $logs->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<!-- Quick Diff Inspection Modal -->
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-layout-split text-primary me-2"></i> Field-Level Change Comparison</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="diffModalMeta" class="mb-3 p-3 bg-light rounded border small">
                    <!-- Populated via AJAX -->
                </div>
                <div class="table-responsive rounded border">
                    <table class="table table-sm table-bordered mb-0 small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th style="width: 25%;">Field</th>
                                <th style="width: 37.5%;" class="text-danger"><i class="bi bi-dash-circle me-1"></i> Previous Value</th>
                                <th style="width: 37.5%;" class="text-success"><i class="bi bi-plus-circle me-1"></i> New Value</th>
                            </tr>
                        </thead>
                        <tbody id="diffModalBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openDiffModal(logId) {
    const metaContainer = document.getElementById('diffModalMeta');
    const bodyContainer = document.getElementById('diffModalBody');

    metaContainer.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Loading diff data...';
    bodyContainer.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted">Loading changes...</td></tr>';

    const modal = new bootstrap.Modal(document.getElementById('diffModal'));
    modal.show();

    fetch('{{ url("staff/audit/compare") }}/' + logId)
        .then(response => response.json())
        .then(data => {
            metaContainer.innerHTML = `
                <div class="row g-2">
                    <div class="col-md-6"><strong>Event ID:</strong> #${data.id} &bull; <strong>Action:</strong> <span class="badge bg-primary">${data.action_label}</span></div>
                    <div class="col-md-6"><strong>User:</strong> ${data.user} &bull; <strong>Timestamp:</strong> ${data.created_at}</div>
                    <div class="col-12 mt-2"><strong>Description:</strong> ${data.description}</div>
                    ${data.reason ? `<div class="col-12 text-warning mt-1"><strong>Reason:</strong> ${data.reason}</div>` : ''}
                </div>
            `;

            if (!data.diff || data.diff.length === 0) {
                bodyContainer.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-muted">No specific field changes captured for this event.</td></tr>';
                return;
            }

            let rows = '';
            data.diff.forEach(item => {
                rows += `
                    <tr>
                        <td class="fw-semibold text-dark">${item.label}</td>
                        <td class="text-danger bg-danger bg-opacity-10 font-monospace">${item.old !== null && item.old !== '' ? item.old : '<span class="text-muted fst-italic">none</span>'}</td>
                        <td class="text-success bg-success bg-opacity-10 font-monospace">${item.new !== null && item.new !== '' ? item.new : '<span class="text-muted fst-italic">none</span>'}</td>
                    </tr>
                `;
            });
            bodyContainer.innerHTML = rows;
        })
        .catch(err => {
            metaContainer.innerHTML = '<div class="text-danger">Failed to load diff information.</div>';
            bodyContainer.innerHTML = '<tr><td colspan="3" class="text-center py-3 text-danger">Error retrieving diff details.</td></tr>';
        });
}
</script>
@endsection
