<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Dentist;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DentistController extends Controller
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    protected function getDentistProfile(): Dentist
    {
        $dentist = Dentist::where('user_id', Auth::id())->first();
        if (!$dentist) {
            abort(403, 'Dentist profile not linked to this account.');
        }
        return $dentist;
    }

    public function dashboard()
    {
        $dentist = $this->getDentistProfile();
        $today = Carbon::today()->format('Y-m-d');

        // Today's schedule
        $todayAppointments = Appointment::with('patient')
            ->where('dentist_id', $dentist->id)
            ->where(function ($q) use ($today) {
                $q->where('confirmed_date', $today)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereNull('confirmed_date')->where('requested_date', $today);
                  });
            })
            ->whereIn('status', [Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED])
            ->orderByRaw('COALESCE(confirmed_time, requested_time) ASC')
            ->get();

        // Upcoming appointments this week
        $startOfWeek = Carbon::today()->startOfWeek();
        $endOfWeek = Carbon::today()->endOfWeek();

        $weekAppointments = Appointment::with('patient')
            ->where('dentist_id', $dentist->id)
            ->whereBetween(
                \DB::raw('COALESCE(confirmed_date, requested_date)'),
                [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')]
            )
            ->orderByRaw('COALESCE(confirmed_date, requested_date) ASC')
            ->orderByRaw('COALESCE(confirmed_time, requested_time) ASC')
            ->get();

        // Metrics
        $stats = [
            'today' => $todayAppointments->count(),
            'upcoming_confirmed' => Appointment::where('dentist_id', $dentist->id)
                ->where('status', Appointment::STATUS_CONFIRMED)
                ->where(function ($q) use ($today) {
                    $q->where('confirmed_date', '>=', $today)
                      ->orWhere(function ($sub) use ($today) {
                          $sub->whereNull('confirmed_date')->where('requested_date', '>=', $today);
                      });
                })->count(),
            'completed' => Appointment::where('dentist_id', $dentist->id)
                ->where('status', Appointment::STATUS_COMPLETED)
                ->count(),
        ];

        return view('dentist.dashboard', compact('dentist', 'todayAppointments', 'weekAppointments', 'stats'));
    }

    public function mySchedule(Request $request)
    {
        $dentist = $this->getDentistProfile();

        $query = Appointment::with('patient')->where('dentist_id', $dentist->id);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($date = $request->get('date')) {
            $query->where(function ($q) use ($date) {
                $q->where('confirmed_date', $date)
                  ->orWhere(function ($sub) use ($date) {
                      $sub->whereNull('confirmed_date')->where('requested_date', $date);
                  });
            });
        }

        $appointments = $query->orderByRaw('COALESCE(confirmed_date, requested_date) DESC')
            ->paginate(15)
            ->withQueryString();

        return view('dentist.schedule', compact('dentist', 'appointments'));
    }

    public function showAppointment(Appointment $appointment)
    {
        $dentist = $this->getDentistProfile();

        // Ensure dentist can only view appointments assigned to them
        if ((int) $appointment->dentist_id !== (int) $dentist->id) {
            abort(403, 'Unauthorized access: You are not assigned to this appointment.');
        }

        $appointment->load(['patient', 'activityLogs.user']);

        return view('dentist.appointment-details', compact('dentist', 'appointment'));
    }

    public function markCompleted(Appointment $appointment)
    {
        $dentist = $this->getDentistProfile();

        if ((int) $appointment->dentist_id !== (int) $dentist->id) {
            abort(403, 'Unauthorized.');
        }

        $this->appointmentService->markCompleted($appointment, Auth::user());

        return back()->with('success', 'Appointment marked as completed successfully.');
    }
}
