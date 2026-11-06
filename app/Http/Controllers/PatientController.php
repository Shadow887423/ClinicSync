<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\SmsNotification;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function dashboard()
    {
        $patient = Auth::user();

        // Upcoming confirmed appointment
        $upcomingConfirmed = Appointment::with('dentist')
            ->where('patient_id', $patient->id)
            ->where('status', Appointment::STATUS_CONFIRMED)
            ->where(function ($query) {
                $query->where('confirmed_date', '>=', now()->toDateString())
                    ->orWhere(function ($q) {
                        $q->whereNull('confirmed_date')
                          ->where('requested_date', '>=', now()->toDateString());
                    });
            })
            ->orderByRaw('COALESCE(confirmed_date, requested_date) ASC')
            ->orderByRaw('COALESCE(confirmed_time, requested_time) ASC')
            ->first();

        // Pending requests
        $pendingRequests = Appointment::with('dentist')
            ->where('patient_id', $patient->id)
            ->where('status', Appointment::STATUS_PENDING)
            ->latest()
            ->get();

        // Recent appointment history (excluding pending)
        $appointmentHistory = Appointment::with('dentist')
            ->where('patient_id', $patient->id)
            ->whereIn('status', [Appointment::STATUS_COMPLETED, Appointment::STATUS_CANCELLED, Appointment::STATUS_REJECTED])
            ->latest()
            ->limit(5)
            ->get();

        // Recent SMS notifications (scoped strictly to authenticated patient)
        $recentNotifications = SmsNotification::where('user_id', $patient->id)
            ->where('recipient_phone', $patient->phone)
            ->latest()
            ->limit(5)
            ->get();

        // Counts
        $stats = [
            'total' => Appointment::where('patient_id', $patient->id)->count(),
            'pending' => $pendingRequests->count(),
            'confirmed' => Appointment::where('patient_id', $patient->id)->where('status', Appointment::STATUS_CONFIRMED)->count(),
            'completed' => Appointment::where('patient_id', $patient->id)->where('status', Appointment::STATUS_COMPLETED)->count(),
        ];

        return view('patient.dashboard', compact('patient', 'upcomingConfirmed', 'pendingRequests', 'appointmentHistory', 'recentNotifications', 'stats'));
    }

    public function profile()
    {
        return view('patient.profile', ['patient' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $patient = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(\+?[0-9]{10,15})$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
        ], [
            'phone.regex' => 'Please provide a valid mobile number (e.g., +15551234567 or 09171234567).',
        ]);

        $patient->update($validated);

        return back()->with('success', 'Profile and mobile contact updated successfully.');
    }

    public function requestForm()
    {
        $dentists = Dentist::where('status', 'active')->with('availabilities')->get();
        $services = [
            'Dental Checkup & Comprehensive Examination',
            'Teeth Cleaning & Scaling (Prophylaxis)',
            'Dental Restoration / Composite Filling',
            'Tooth Extraction (Simple / Surgical)',
            'Root Canal Therapy Consultation',
            'Orthodontic Consultation (Braces & Aligners)',
            'Teeth Whitening & Cosmetic Dental Care',
            'Pediatric Dental Checkup & Fluoride Application',
        ];

        return view('patient.request-appointment', compact('dentists', 'services'));
    }

    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'dentist_id' => 'required|exists:dentists,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $slots = $this->appointmentService->getAvailableSlotsForDentist(
            (int)$request->dentist_id,
            $request->date
        );

        return response()->json([
            'status' => 'success',
            'slots' => $slots,
        ]);
    }

    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'dentist_id' => ['required', 'exists:dentists,id'],
            'service_type' => ['required', 'string', 'max:255'],
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'requested_time' => ['required'],
            'patient_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment = $this->appointmentService->requestAppointment(Auth::user(), $validated);

        return redirect()->route('patient.appointments.show', $appointment)
            ->with('success', 'Your appointment request has been submitted successfully! Status is Pending review by clinic staff.');
    }

    public function myAppointments(Request $request)
    {
        $status = $request->get('status');
        $query = Appointment::with('dentist')->where('patient_id', Auth::id());

        if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'reschedule_requested', 'cancelled', 'rejected'])) {
            $query->where('status', $status);
        }

        $appointments = $query->latest()->paginate(10)->withQueryString();

        return view('patient.my-appointments', compact('appointments', 'status'));
    }

    public function showAppointment(Appointment $appointment)
    {
        // Enforce authorization: patient can only view their own appointments
        if ((int)$appointment->patient_id !== (int)Auth::id()) {
            abort(403, 'Unauthorized access to this appointment.');
        }

        $appointment->load(['dentist', 'activityLogs.user', 'smsNotifications']);

        return view('patient.appointment-details', compact('appointment'));
    }

    public function rescheduleForm(Appointment $appointment)
    {
        if ((int)$appointment->patient_id !== (int)Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$appointment->canBeRescheduled()) {
            return redirect()->route('patient.appointments.show', $appointment)
                ->with('error', 'This appointment cannot be rescheduled.');
        }

        $dentists = Dentist::where('status', 'active')->get();

        return view('patient.reschedule', compact('appointment', 'dentists'));
    }

    public function storeReschedule(Request $request, Appointment $appointment)
    {
        if ($appointment->patient_id !== Auth::id()) {
            abort(403, 'Unauthorized access: You cannot reschedule an appointment that does not belong to you.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_time' => ['nullable'],
        ]);

        $this->appointmentService->requestReschedule($appointment, $validated, Auth::user());

        return redirect()->route('patient.appointments.show', $appointment)
            ->with('success', 'Reschedule request submitted. Clinic staff will review and notify you.');
    }

    public function cancelAppointment(Request $request, Appointment $appointment)
    {
        if ($appointment->patient_id !== Auth::id()) {
            abort(403, 'Unauthorized access: You cannot cancel an appointment that does not belong to you.');
        }

        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->appointmentService->cancelAppointment($appointment, $request->cancellation_reason, Auth::user());

        return redirect()->route('patient.appointments.show', $appointment)
            ->with('info', 'Your appointment has been cancelled. An SMS update has been sent.');
    }

    public function notifications()
    {
        $patient = Auth::user();
        $notifications = SmsNotification::where('user_id', $patient->id)
            ->where('recipient_phone', $patient->phone)
            ->latest()
            ->paginate(15);

        return view('patient.notifications', compact('notifications'));
    }
}
