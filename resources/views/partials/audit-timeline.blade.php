<div class="audit-timeline-container position-relative">
    <div class="timeline-line position-absolute top-0 bottom-0 start-0 ms-3 border-start border-2 border-primary border-opacity-25" style="z-index: 1;"></div>

    <div class="d-flex flex-column gap-4 position-relative" style="z-index: 2;">
        @forelse($timeline as $index => $event)
            <div class="timeline-item d-flex gap-3 align-items-start">
                <!-- Marker Icon -->
                <div class="rounded-circle bg-white border border-2 shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 32px; height: 32px; z-index: 3;">
                    <i class="bi {{ $event->action_icon }} fs-6"></i>
                </div>

                <!-- Event Card -->
                <div class="card card-custom w-100 p-3 shadow-sm border">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2 pb-2 border-bottom">
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge {{ $event->action_badge_class }} text-uppercase px-2 py-1" style="font-size: 0.7rem;">
                                    {{ str_replace('_', ' ', $event->action) }}
                                </span>
                                <h6 class="fw-bold mb-0 text-dark">{{ $event->description }}</h6>
                            </div>
                            <div class="text-muted small mt-1">
                                <span class="fw-semibold text-secondary">Event #{{ $event->id }}</span> &bull; 
                                <span title="{{ $event->created_at->format('Y-m-d H:i:s T') }}">
                                    <i class="bi bi-clock me-1"></i>{{ $event->created_at->format('M d, Y \a\t g:i:s A') }}
                                </span>
                                <span class="text-muted">({{ $event->created_at->diffForHumans() }})</span>
                            </div>
                        </div>

                        <!-- User Responsible Pill -->
                        <div class="d-flex align-items-center gap-2 bg-light px-3 py-1 rounded-pill border">
                            <i class="bi bi-person-fill text-muted"></i>
                            <div class="small">
                                <span class="fw-bold text-dark">{{ $event->user_name ?? 'System' }}</span>
                                @if($event->user_role)
                                    <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">{{ strtoupper($event->user_role) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Event Reason / Notes if provided -->
                    @if($event->reason)
                        <div class="alert alert-warning py-2 px-3 small mb-2 border-start border-4 border-warning">
                            <div class="fw-bold"><i class="bi bi-chat-quote-fill me-1"></i> Recorded Reason / Note:</div>
                            <div class="mt-1">{{ $event->reason }}</div>
                        </div>
                    @endif

                    <!-- Field-by-Field Diff Comparison -->
                    @if(!empty($event->diff))
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2 small d-inline-flex align-items-center gap-1 mb-2" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#diff-{{ $event->id }}" 
                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                                <i class="bi bi-layout-split"></i>
                                <span>Field Changes ({{ count($event->diff) }})</span>
                                <i class="bi bi-chevron-down ms-1" style="font-size: 0.75rem;"></i>
                            </button>

                            <div class="collapse {{ $index === 0 ? 'show' : '' }}" id="diff-{{ $event->id }}">
                                <div class="table-responsive rounded border bg-light">
                                    <table class="table table-sm table-bordered mb-0 small">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th style="width: 25%;">Modified Field</th>
                                                <th style="width: 37.5%;" class="text-danger"><i class="bi bi-dash-circle me-1"></i> Previous Value</th>
                                                <th style="width: 37.5%;" class="text-success"><i class="bi bi-plus-circle me-1"></i> New Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($event->diff as $diffItem)
                                                <tr>
                                                    <td class="fw-semibold text-dark">{{ $diffItem['label'] }}</td>
                                                    <td class="text-danger bg-danger bg-opacity-10 font-monospace">
                                                        @if($diffItem['old'] !== null && $diffItem['old'] !== '')
                                                            {{ $diffItem['old'] }}
                                                        @else
                                                            <span class="text-muted fst-italic">none</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-success bg-success bg-opacity-10 font-monospace">
                                                        @if($diffItem['new'] !== null && $diffItem['new'] !== '')
                                                            {{ $diffItem['new'] }}
                                                        @else
                                                            <span class="text-muted fst-italic">none</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Technical Audit Meta -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top text-muted" style="font-size: 0.72rem;">
                        <div>
                            <span class="me-3"><i class="bi bi-hdd-network me-1"></i> IP: <code>{{ $event->ip_address ?: '127.0.0.1' }}</code></span>
                            @if($event->user_agent)
                                <span class="d-none d-md-inline text-truncate" style="max-width: 300px;" title="{{ $event->user_agent }}">
                                    <i class="bi bi-laptop me-1"></i> {{ Str::limit($event->user_agent, 45) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <span class="badge bg-light text-muted border">Result: {{ strtoupper($event->action_result) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <i class="bi bi-clock-history text-muted fs-1 mb-2"></i>
                <h6 class="text-muted">No audit activity recorded for this item yet.</h6>
            </div>
        @endforelse
    </div>
</div>
