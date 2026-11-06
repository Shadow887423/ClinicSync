@extends('layouts.dashboard')

@section('title', 'Request Dental Appointment')
@section('page-title', 'Book a Dental Appointment')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-custom p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <div>
                    <h4 class="fw-bold text-dark mb-1">New Appointment Request</h4>
                    <p class="text-muted small mb-0">Select your dental service, doctor, preferred date, and available time slot.</p>
                </div>
                <span class="badge bg-teal" style="background-color: var(--secondary-teal);">Step-by-Step Booking</span>
            </div>

            <form action="{{ route('patient.appointments.store') }}" method="POST" id="appointmentForm">
                @csrf

                <!-- Step 1: Dental Procedure / Service -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-dark">
                        1. Select Dental Service / Reason for Visit <span class="text-danger">*</span>
                    </label>
                    <select name="service_type" id="service_type" class="form-select form-select-lg" required>
                        <option value="">-- Choose Dental Service --</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc }}" {{ old('service_type') == $svc ? 'selected' : '' }}>
                                {{ $svc }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Step 2: Preferred Dentist -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-dark">
                        2. Select Dentist <span class="text-danger">*</span>
                    </label>
                    <select name="dentist_id" id="dentist_id" class="form-select form-select-lg" required>
                        <option value="">-- Choose a Dentist --</option>
                        @foreach($dentists as $d)
                            <option value="{{ $d->id }}" data-duration="{{ $d->slot_duration_minutes }}" {{ old('dentist_id') == $d->id ? 'selected' : '' }}>
                                {{ $d->name }} — {{ $d->specialization }} ({{ $d->slot_duration_minutes }} min slots)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Step 3: Appointment Date -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-dark">
                        3. Choose Appointment Date <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                        <input type="date" name="requested_date" id="requested_date" class="form-control" 
                               min="{{ now()->format('Y-m-d') }}" 
                               value="{{ old('requested_date', now()->addDay()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-text small">Select a date to query real-time schedule availability for the chosen dentist.</div>
                </div>

                <!-- Step 4: Available Slots Loaded via AJAX -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold text-dark mb-0">
                            4. Select Available Time Slot <span class="text-danger">*</span>
                        </label>
                        <span id="slotsLoading" class="text-muted small d-none">
                            <span class="spinner-border spinner-border-sm text-teal me-1"></span> Loading available slots...
                        </span>
                    </div>

                    <!-- Hidden input to store chosen slot time -->
                    <input type="hidden" name="requested_time" id="requested_time" value="{{ old('requested_time') }}" required>

                    <div id="slotsContainer" class="p-3 bg-light rounded-3 border min-h-80">
                        <div class="text-muted small text-center py-3" id="slotsPlaceholder">
                            <i class="bi bi-clock-history d-block fs-3 mb-1 text-secondary opacity-50"></i>
                            Please choose a dentist and date above to display available time slots.
                        </div>
                        <div id="slotsGrid" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div id="selectedSlotAlert" class="mt-2 small text-success fw-bold d-none">
                        <i class="bi bi-check-circle-fill me-1"></i> Selected Time Slot: <span id="selectedSlotText"></span>
                    </div>
                </div>

                <!-- Step 5: Notes & Symptoms -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-dark">
                        5. Patient Notes / Symptoms (Optional)
                    </label>
                    <textarea name="patient_notes" class="form-control" rows="3" placeholder="Describe any current symptoms, sensitivity, or previous treatments relevant to your visit...">{{ old('patient_notes') }}</textarea>
                </div>

                <!-- SMS Notification Notice -->
                <div class="p-3 mb-4 rounded-3 bg-light border border-teal-subtle d-flex align-items-start gap-3">
                    <i class="bi bi-chat-heart text-teal fs-4" style="color: var(--secondary-teal);"></i>
                    <div class="small">
                        <div class="fw-bold text-dark mb-1">Automated SMS Notification Notice</div>
                        <p class="text-muted mb-0">
                            Upon submission, your request will be in <strong>Pending</strong> status. Clinic staff will verify schedule availability. Once confirmed, you will instantly receive an SMS text message on <strong>{{ Auth::user()->phone }}</strong> with final appointment details.
                        </p>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('patient.dashboard') }}" class="btn btn-light border rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-teal rounded-pill px-5 fw-bold" id="submitBtn">
                        <i class="bi bi-calendar-check me-1"></i> Submit Appointment Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dentistSelect = document.getElementById('dentist_id');
        const dateInput = document.getElementById('requested_date');
        const slotsContainer = document.getElementById('slotsContainer');
        const slotsPlaceholder = document.getElementById('slotsPlaceholder');
        const slotsGrid = document.getElementById('slotsGrid');
        const slotsLoading = document.getElementById('slotsLoading');
        const requestedTimeInput = document.getElementById('requested_time');
        const selectedSlotAlert = document.getElementById('selectedSlotAlert');
        const selectedSlotText = document.getElementById('selectedSlotText');
        const appointmentForm = document.getElementById('appointmentForm');

        function fetchSlots() {
            const dentistId = dentistSelect.value;
            const date = dateInput.value;

            if (!dentistId || !date) {
                slotsGrid.innerHTML = '';
                slotsPlaceholder.classList.remove('d-none');
                requestedTimeInput.value = '';
                selectedSlotAlert.classList.add('d-none');
                return;
            }

            slotsLoading.classList.remove('d-none');
            slotsPlaceholder.classList.add('d-none');
            slotsGrid.innerHTML = '';
            requestedTimeInput.value = '';
            selectedSlotAlert.classList.add('d-none');

            fetch(`{{ route('patient.appointments.slots') }}?dentist_id=${dentistId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    slotsLoading.classList.add('d-none');
                    if (data.status === 'success' && data.slots && data.slots.length > 0) {
                        let hasAvailable = false;
                        data.slots.forEach(slot => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'btn btn-sm m-1 rounded-pill ' + 
                                (slot.is_available ? 'btn-outline-primary' : 'btn-light border text-muted disabled');
                            btn.innerText = slot.display + (slot.is_booked ? ' (Booked)' : '');
                            
                            if (slot.is_available) {
                                hasAvailable = true;
                                btn.addEventListener('click', function() {
                                    // Highlight active
                                    document.querySelectorAll('#slotsGrid .btn').forEach(b => {
                                        b.classList.remove('btn-primary');
                                        b.classList.add('btn-outline-primary');
                                    });
                                    this.classList.remove('btn-outline-primary');
                                    this.classList.add('btn-primary');

                                    requestedTimeInput.value = slot.time;
                                    selectedSlotText.innerText = slot.display;
                                    selectedSlotAlert.classList.remove('d-none');
                                });
                            }
                            slotsGrid.appendChild(btn);
                        });

                        if (!hasAvailable) {
                            slotsGrid.innerHTML = '<div class="text-danger small py-2"><i class="bi bi-exclamation-triangle me-1"></i> All slots for this date are fully booked or in the past. Please choose another date.</div>';
                        }
                    } else {
                        slotsGrid.innerHTML = '<div class="text-warning small py-2"><i class="bi bi-calendar-x me-1"></i> Dentist is not available on this day of the week. Please select another date.</div>';
                    }
                })
                .catch(err => {
                    slotsLoading.classList.add('d-none');
                    slotsGrid.innerHTML = '<div class="text-danger small py-2">Error checking available slots. Please try again.</div>';
                });
        }

        dentistSelect.addEventListener('change', fetchSlots);
        dateInput.addEventListener('change', fetchSlots);

        // Initial load if old values exist
        if (dentistSelect.value && dateInput.value) {
            fetchSlots();
        }

        appointmentForm.addEventListener('submit', function(e) {
            if (!requestedTimeInput.value) {
                e.preventDefault();
                alert('Please click and select an available time slot before submitting.');
            }
        });
    });
</script>
@endsection
