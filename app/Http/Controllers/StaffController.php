<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Models\DentistAvailability;
use App\Models\SmsNotification;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\SmsGatewayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    protected AppointmentService $appointmentService;
    protected SmsGatewayService $smsService;

    public function __construct(AppointmentService $appointmentService, SmsGatewayService $smsService)
    {
        $this->appointmentService = $appointmentService;
        $this->smsService = $smsService;
    }

    public function dashboard()
    {
        $today = Carbon::today()->format('Y-m-d');

        // Summary metrics
        $metrics = [
            'total' => Appointment::count(),
            'pending' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
            'confirmed' => Appointment::where('status', Appointment::STATUS_CONFIRMED)->count(),
            'today' => Appointment::where(function ($q) use ($today) {
                $q->where('confirmed_date', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereNull('confirmed_date')->where('requested_date', $today);
                  });
            })->count(),
            'cancelled' => Appointment::where('status', Appointment::STATUS_CANCELLED)->count(),
            'completed' => Appointment::where('status', Appointment::STATUS_COMPLETED)->count(),
        ];

        // Pending queue
        $pendingQueue = Appointment::with(['patient', 'dentist'])
            ->where('status', Appointment::STATUS_PENDING)
            ->oldest('requested_date')
            ->limit(5)
            ->get();

        // Today's appointments
        $todayAppointments = Appointment::with(['patient', 'dentist'])
            ->where(function ($q) use ($today) {
                $q->where('confirmed_date', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereNull('confirmed_date')->where('requested_date', $today);
                  });
            })
            ->orderByRaw('COALESCE(confirmed_time, requested_time) ASC')
            ->get();

        // Recent SMS activities
        $recentSms = SmsNotification::with('appointment')
            ->latest()
            ->limit(6)
            ->get();

        // Database-driven chart data: Status breakdown
        $statusCounts = Appointment::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $chartStatusLabels = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rejected'];
        $chartStatusValues = [
            $statusCounts[Appointment::STATUS_PENDING] ?? 0,
            $statusCounts[Appointment::STATUS_CONFIRMED] ?? 0,
            $statusCounts[Appointment::STATUS_COMPLETED] ?? 0,
            $statusCounts[Appointment::STATUS_CANCELLED] ?? 0,
            $statusCounts[Appointment::STATUS_REJECTED] ?? 0,
        ];

        // Database-driven chart data: Next 7 days volume
        $next7DaysLabels = [];
        $next7DaysValues = [];
        for ($i = 0; $i < 7; $i++) {
            $day = Carbon::today()->addDays($i);
            $dayStr = $day->format('Y-m-d');
            $next7DaysLabels[] = $day->format('D, M d');
            $next7DaysValues[] = Appointment::where(function ($q) use ($dayStr) {
                $q->where('confirmed_date', $dayStr)
                  ->orWhere(function ($sub) use ($dayStr) {
                      $sub->whereNull('confirmed_date')->where('requested_date', $dayStr);
                  });
            })->count();
        }

        $dentists = Dentist::where('status', 'active')->get();

        return view('staff.dashboard', compact(
            'metrics',
            'pendingQueue',
            'todayAppointments',
            'recentSms',
            'chartStatusLabels',
            'chartStatusValues',
            'next7DaysLabels',
            'next7DaysValues',
            'dentists'
        ));
    }

    public function appointments(Request $request)
    {
        $query = Appointment::with(['patient', 'dentist']);

        // Filter: Search by patient name, phone, or appointment number
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($p) use ($search) {
                      $p->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter: Dentist
        if ($dentistId = $request->get('dentist_id')) {
            $query->where('dentist_id', $dentistId);
        }

        // Filter: Date
        if ($date = $request->get('date')) {
            $query->where(function ($q) use ($date) {
                $q->where('confirmed_date', $date)
                  ->orWhere(function ($sub) use ($date) {
                      $sub->whereNull('confirmed_date')->where('requested_date', $date);
                  });
            });
        }

        $appointments = $query->latest()->paginate(15)->withQueryString();
        $dentists = Dentist::where('status', 'active')->get();

        return view('staff.appointments', compact('appointments', 'dentists'));
    }

    public function showAppointment(Appointment $appointment)
    {
        $appointment->load(['patient', 'dentist', 'activityLogs.user', 'smsNotifications']);
        $dentists = Dentist::where('status', 'active')->get();

        return view('staff.appointment-details', compact('appointment', 'dentists'));
    }

    public function confirm(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'dentist_id' => ['required', 'exists:dentists,id'],
            'confirmed_date' => ['required', 'date'],
            'confirmed_time' => ['required'],
            'staff_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->appointmentService->confirmAppointment($appointment, $validated, Auth::user());

        return back()->with('success', 'Appointment confirmed successfully! Automated SMS notification has been dispatched.');
    }

    public function reject(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->appointmentService->rejectAppointment($appointment, $validated['rejection_reason'], Auth::user());

        return back()->with('info', 'Appointment request has been rejected. Notification sent to patient.');
    }

    public function modify(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'dentist_id' => ['required', 'exists:dentists,id'],
            'date' => ['required', 'date'],
            'time' => ['required'],
            'staff_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->appointmentService->modifyAppointment($appointment, $validated, Auth::user());

        return back()->with('success', 'Appointment schedule updated successfully.');
    }

    public function complete(Appointment $appointment)
    {
        $this->appointmentService->markCompleted($appointment, Auth::user());

        return back()->with('success', 'Appointment marked as completed.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->appointmentService->cancelAppointment($appointment, $validated['cancellation_reason'], Auth::user());

        return back()->with('info', 'Appointment cancelled. Patient notified via SMS.');
    }

    public function calendar(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth = Carbon::parse($month)->endOfMonth();

        $appointments = Appointment::with(['patient', 'dentist'])
            ->whereBetween(DB::raw('COALESCE(confirmed_date, requested_date)'), [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get();

        $eventsByDate = [];
        foreach ($appointments as $apt) {
            $d = $apt->effective_date->format('Y-m-d');
            $eventsByDate[$d][] = $apt;
        }

        $dentists = Dentist::where('status', 'active')->get();

        return view('staff.calendar', compact('startOfMonth', 'month', 'eventsByDate', 'dentists'));
    }

    public function patients(Request $request)
    {
        $query = User::where('role', User::ROLE_PATIENT)->withCount('appointments');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(15)->withQueryString();

        return view('staff.patients', compact('patients'));
    }

    public function dentists()
    {
        $dentists = Dentist::with(['user', 'availabilities'])->get();
        return view('staff.dentists', compact('dentists'));
    }

    public function storeDentist(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string'],
            'specialization' => ['required', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'slot_duration_minutes' => ['required', 'integer', 'in:15,30,45,60'],
            'bio' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->role = User::ROLE_DENTIST;
        $user->status = 'active';
        $user->save();

        $dentist = Dentist::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'specialization' => $validated['specialization'],
            'license_number' => $validated['license_number'] ?? null,
            'slot_duration_minutes' => $validated['slot_duration_minutes'],
            'bio' => $validated['bio'] ?? null,
            'status' => 'active',
        ]);

        // Default weekday schedule (Mon-Fri 09:00 - 17:00)
        $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
        foreach ($dayNames as $dayNumber => $dayName) {
            DentistAvailability::create([
                'dentist_id' => $dentist->id,
                'day_of_week' => $dayNumber,
                'day_name' => $dayName,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'slot_duration_minutes' => $validated['slot_duration_minutes'],
                'status' => 'active',
            ]);
        }

        return back()->with('success', "Dentist {$dentist->name} created successfully with default Mon-Fri availability schedule.");
    }

    public function updateDentist(Request $request, Dentist $dentist)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'specialization' => ['required', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string'],
            'slot_duration_minutes' => ['required', 'integer', 'in:15,30,45,60'],
            'status' => ['required', 'in:active,inactive'],
            'bio' => ['nullable', 'string'],
        ]);

        $dentist->update($validated);
        if ($dentist->user) {
            $dentist->user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
            ]);
        }

        return back()->with('success', 'Dentist profile updated successfully.');
    }

    public function updateAvailability(Request $request, Dentist $dentist)
    {
        $days = $request->input('days', []);
        $dayNames = [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
        ];

        // Delete existing and rebuild
        $dentist->availabilities()->delete();

        foreach ($days as $dayIndex => $data) {
            if (isset($data['active']) && $data['active'] == '1') {
                DentistAvailability::create([
                    'dentist_id' => $dentist->id,
                    'day_of_week' => (int)$dayIndex,
                    'day_name' => $dayNames[(int)$dayIndex] ?? 'Day ' . $dayIndex,
                    'start_time' => $data['start_time'] ?? '09:00:00',
                    'end_time' => $data['end_time'] ?? '17:00:00',
                    'slot_duration_minutes' => $dentist->slot_duration_minutes ?: 30,
                    'status' => 'active',
                ]);
            }
        }

        return back()->with('success', "Schedule availability updated for {$dentist->name}.");
    }

    public function notificationLogs(Request $request)
    {
        $query = SmsNotification::with(['appointment.patient', 'user']);

        if ($type = $request->get('type')) {
            $query->where('notification_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('gateway_reference', 'like', "%{$search}%");
            });
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        return view('staff.notifications', compact('notifications'));
    }

    public function retryNotification(SmsNotification $notification)
    {
        $this->smsService->retryNotification($notification);

        return back()->with('success', 'Notification re-dispatched to SMS Gateway.');
    }

    public function settings()
    {
        $gateway = config('sms.provider', 'simulated');
        $isSimulation = config('sms.simulation_enabled', true);
        $reminderHours = config('sms.reminder_hours_before', 24);

        $stats = [
            'total_sent' => SmsNotification::whereIn('status', ['sent', 'delivered', 'simulated'])->count(),
            'total_failed' => SmsNotification::where('status', 'failed')->count(),
            'total_simulated' => SmsNotification::where('status', 'simulated')->count(),
        ];

        return view('staff.settings', compact('gateway', 'isSimulation', 'reminderHours', 'stats'));
    }

    public function testSms(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'message' => ['required', 'string', 'max:160'],
        ]);

        $notification = $this->smsService->sendSms(
            $request->phone,
            $request->message,
            SmsNotification::TYPE_GENERAL,
            null,
            Auth::id()
        );

        if ($notification->status === SmsNotification::STATUS_FAILED) {
            return back()->with('error', 'Test SMS failed: ' . $notification->error_message);
        }

        return back()->with('success', "Test SMS successfully dispatched! Status: {$notification->status} (Ref: {$notification->gateway_reference})");
    }
}
