@extends('layouts.dashboard')

@section('title', 'Master Appointment Calendar')
@section('page-title', 'Clinic Master Calendar')

@section('content')
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">
                <i class="bi bi-calendar3 text-teal me-2" style="color: var(--secondary-teal);"></i>
                {{ $startOfMonth->format('F Y') }}
            </h4>
            <div class="text-muted small">Overview of confirmed and pending appointments across all clinic dentists.</div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('staff.calendar', ['month' => $startOfMonth->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-sm btn-light border rounded-pill px-3">
                <i class="bi bi-chevron-left me-1"></i> Previous Month
            </a>
            <a href="{{ route('staff.calendar', ['month' => now()->format('Y-m')]) }}" class="btn btn-sm btn-teal rounded-pill px-3">
                Today
            </a>
            <a href="{{ route('staff.calendar', ['month' => $startOfMonth->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-sm btn-light border rounded-pill px-3">
                Next Month <i class="bi bi-chevron-right ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 14.28%;">Sun</th>
                    <th style="width: 14.28%;">Mon</th>
                    <th style="width: 14.28%;">Tue</th>
                    <th style="width: 14.28%;">Wed</th>
                    <th style="width: 14.28%;">Thu</th>
                    <th style="width: 14.28%;">Fri</th>
                    <th style="width: 14.28%;">Sat</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $startDayOfWeek = $startOfMonth->dayOfWeek; // 0=Sunday
                    $daysInMonth = $startOfMonth->daysInMonth;
                    $dayCounter = 1;
                    $currentDay = $startOfMonth->copy();
                @endphp

                @for($row = 0; $row < 6; $row++)
                    @if($dayCounter > $daysInMonth && $row > 0)
                        @break
                    @endif
                    <tr>
                        @for($col = 0; $col < 7; $col++)
                            @if(($row === 0 && $col < $startDayOfWeek) || $dayCounter > $daysInMonth)
                                <td class="bg-light text-muted opacity-50" style="height: 110px;"></td>
                            @else
                                @php
                                    $dateStr = $startOfMonth->copy()->day($dayCounter)->format('Y-m-d');
                                    $isToday = ($dateStr === now()->format('Y-m-d'));
                                    $dayAppointments = $eventsByDate[$dateStr] ?? [];
                                @endphp
                                <td class="p-1 align-top {{ $isToday ? 'bg-primary bg-opacity-10 border-primary' : '' }}" style="height: 110px;">
                                    <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                                        <span class="small fw-bold {{ $isToday ? 'badge bg-primary' : 'text-dark' }}">
                                            {{ $dayCounter }}
                                        </span>
                                        @if(count($dayAppointments) > 0)
                                            <span class="badge bg-secondary text-white" style="font-size: 0.65rem;">
                                                {{ count($dayAppointments) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="d-flex flex-column gap-1 overflow-auto" style="max-height: 80px;">
                                        @foreach($dayAppointments as $apt)
                                            <a href="{{ route('staff.appointments.show', $apt) }}" 
                                               class="text-decoration-none badge {{ $apt->status_badge_class }} text-start p-1 text-truncate" 
                                               title="{{ $apt->service_type }} ({{ $apt->patient ? $apt->patient->name : '' }})">
                                                {{ \Carbon\Carbon::parse($apt->effective_time)->format('g:i A') }} - {{ $apt->patient ? $apt->patient->name : 'Patient' }}
                                            </a>
                                        @endforeach
                                    </div>
                                </td>
                                @php $dayCounter++; @endphp
                            @endif
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
@endsection
