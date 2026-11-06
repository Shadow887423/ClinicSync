<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentActivityLog;
use App\Models\Dentist;
use App\Models\DentistAvailability;
use App\Models\SmsNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $defaultPassword = Hash::make('password123');

        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@clinicsync.com'],
            [
                'name' => 'Clinic Administrator (Dr. Arthur Bennett)',
                'role' => User::ROLE_ADMIN,
                'phone' => '+15551234567',
                'status' => 'active',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        );

        // 2. Staff User
        $staff = User::firstOrCreate(
            ['email' => 'staff@clinicsync.com'],
            [
                'name' => 'Emily Watson (Head Receptionist)',
                'role' => User::ROLE_STAFF,
                'phone' => '+15552345678',
                'status' => 'active',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]
        );

        // 3. Dentists and User Accounts
        $dentistData = [
            [
                'name' => 'Dr. Aris Thorne, DMD',
                'email' => 'dr.thorne@clinicsync.com',
                'phone' => '+15553456789',
                'specialization' => 'General Dentistry & Orthodontics',
                'license_number' => 'DEN-98214',
                'slot_duration_minutes' => 30,
                'bio' => 'Board-certified dental surgeon with over 12 years of clinical experience in preventative care, orthodontics, and restorative procedures.',
                'days' => [1, 2, 3, 4, 5, 6], // Mon - Sat
            ],
            [
                'name' => 'Dr. Maya Lin, DDS',
                'email' => 'dr.lin@clinicsync.com',
                'phone' => '+15554567890',
                'specialization' => 'Endodontics & Restorative Dentistry',
                'license_number' => 'DEN-77341',
                'slot_duration_minutes' => 45,
                'bio' => 'Specialist in microscopic endodontics, pain management, and root canal therapy. Dedicated to patient comfort and painless dental care.',
                'days' => [1, 2, 3, 4, 5], // Mon - Fri
            ],
            [
                'name' => 'Dr. Marcus Vance, DMD',
                'email' => 'dr.vance@clinicsync.com',
                'phone' => '+15555678901',
                'specialization' => 'Pediatric & Preventive Dentistry',
                'license_number' => 'DEN-66129',
                'slot_duration_minutes' => 30,
                'bio' => 'Passionate about family oral health, pediatric sealants, early orthodontic intervention, and patient education.',
                'days' => [2, 3, 4, 5, 6], // Tue - Sat
            ],
        ];

        $dayNames = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        $dentistModels = [];

        foreach ($dentistData as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'role' => User::ROLE_DENTIST,
                    'phone' => $d['phone'],
                    'status' => 'active',
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]
            );

            $dentist = Dentist::firstOrCreate(
                ['email' => $d['email']],
                [
                    'user_id' => $user->id,
                    'name' => $d['name'],
                    'specialization' => $d['specialization'],
                    'license_number' => $d['license_number'],
                    'phone' => $d['phone'],
                    'slot_duration_minutes' => $d['slot_duration_minutes'],
                    'status' => 'active',
                    'bio' => $d['bio'],
                ]
            );

            $dentistModels[] = $dentist;

            // Create availability records
            foreach ($d['days'] as $dayNumber) {
                DentistAvailability::firstOrCreate(
                    [
                        'dentist_id' => $dentist->id,
                        'day_of_week' => $dayNumber,
                    ],
                    [
                        'day_name' => $dayNames[$dayNumber],
                        'start_time' => '09:00:00',
                        'end_time' => ($dayNumber === 6) ? '13:00:00' : '17:00:00',
                        'slot_duration_minutes' => $d['slot_duration_minutes'],
                        'status' => 'active',
                    ]
                );
            }
        }

        // 4. Patients
        $patientsData = [
            [
                'name' => 'John Doe (Demo Patient)',
                'email' => 'patient@clinicsync.com',
                'phone' => '+15559876543',
                'address' => '742 Evergreen Terrace, Springfield',
                'birth_date' => '1992-05-14',
                'gender' => 'Male',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@example.com',
                'phone' => '+15558765432',
                'address' => '120 Rizal Blvd, Manila',
                'birth_date' => '1988-11-20',
                'gender' => 'Female',
            ],
            [
                'name' => 'David Kim',
                'email' => 'david.kim@example.com',
                'phone' => '+15557654321',
                'address' => '303 Pine Crest Ave, Seattle, WA',
                'birth_date' => '1995-03-08',
                'gender' => 'Male',
            ],
            [
                'name' => 'Chloe Bennet',
                'email' => 'chloe.b@example.com',
                'phone' => '+15556543210',
                'address' => '404 Ocean Drive, San Diego, CA',
                'birth_date' => '1999-07-25',
                'gender' => 'Female',
            ],
        ];

        $patientModels = [];
        foreach ($patientsData as $p) {
            $patient = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'role' => User::ROLE_PATIENT,
                    'phone' => $p['phone'],
                    'address' => $p['address'],
                    'birth_date' => $p['birth_date'],
                    'gender' => $p['gender'],
                    'status' => 'active',
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]
            );
            $patientModels[] = $patient;
        }

        $mainPatient = $patientModels[0]; // John Doe

        // 5. Appointments & Activity Logs & SMS Notifications
        // Let's create a range of appointments:
        // A. Confirmed Today / Tomorrow
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $nextWeek = Carbon::today()->addDays(5);
        $pastTwoWeeks = Carbon::today()->subDays(14);
        $pastMonth = Carbon::today()->subDays(30);

        // Appointment 1: Confirmed upcoming for John Doe
        $apt1 = Appointment::create([
            'appointment_number' => 'APT-' . $today->format('Ymd') . '-001',
            'patient_id' => $mainPatient->id,
            'dentist_id' => $dentistModels[0]->id,
            'service_type' => 'Dental Checkup & Cleaning',
            'requested_date' => $tomorrow->format('Y-m-d'),
            'requested_time' => '10:00:00',
            'confirmed_date' => $tomorrow->format('Y-m-d'),
            'confirmed_time' => '10:00:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'patient_notes' => 'Routine biannual checkup and mild sensitivity on upper molar.',
            'staff_notes' => 'Confirmed with Dr. Thorne. Patient notified via SMS.',
        ]);

        $apt1->logActivity('created', null, Appointment::STATUS_PENDING, 'Patient submitted online booking request.', $mainPatient->id);
        $apt1->logActivity('confirmed', Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED, 'Staff confirmed slot for ' . $tomorrow->format('M d, Y') . ' 10:00 AM.', $staff->id);

        SmsNotification::create([
            'appointment_id' => $apt1->id,
            'user_id' => $mainPatient->id,
            'recipient_phone' => $mainPatient->phone,
            'notification_type' => SmsNotification::TYPE_CONFIRMATION,
            'message' => "Hello {$mainPatient->name}, your dental appointment has been confirmed for {$tomorrow->format('Y-m-d')} at 10:00 AM. Dentist: {$dentistModels[0]->name}. Please contact the clinic if you need to reschedule. — CLINICSYNC",
            'status' => SmsNotification::STATUS_SIMULATED,
            'gateway' => 'simulated',
            'gateway_reference' => 'SIM-' . uniqid(),
            'sent_at' => now()->subHours(2),
        ]);

        SmsNotification::create([
            'appointment_id' => $apt1->id,
            'user_id' => $mainPatient->id,
            'recipient_phone' => $mainPatient->phone,
            'notification_type' => SmsNotification::TYPE_REMINDER,
            'message' => "Reminder: Hello {$mainPatient->name}, your dental appointment is scheduled for {$tomorrow->format('Y-m-d')} at 10:00 AM. Dentist: {$dentistModels[0]->name}. Please contact the clinic if you need to reschedule. — CLINICSYNC",
            'status' => SmsNotification::STATUS_QUEUED,
            'gateway' => 'simulated',
            'scheduled_at' => $tomorrow->copy()->subHours(24),
        ]);

        // Appointment 2: Pending request for Maria Santos
        $apt2 = Appointment::create([
            'appointment_number' => 'APT-' . $today->format('Ymd') . '-002',
            'patient_id' => $patientModels[1]->id,
            'dentist_id' => $dentistModels[1]->id,
            'service_type' => 'Root Canal Consultation',
            'requested_date' => $nextWeek->format('Y-m-d'),
            'requested_time' => '14:00:00',
            'status' => Appointment::STATUS_PENDING,
            'patient_notes' => 'Experiencing sharp pain on lower right tooth when chewing.',
        ]);
        $apt2->logActivity('created', null, Appointment::STATUS_PENDING, 'Patient submitted online booking request.', $patientModels[1]->id);

        // Appointment 3: Confirmed today for David Kim
        $apt3 = Appointment::create([
            'appointment_number' => 'APT-' . $today->format('Ymd') . '-003',
            'patient_id' => $patientModels[2]->id,
            'dentist_id' => $dentistModels[0]->id,
            'service_type' => 'Tooth Extraction Assessment',
            'requested_date' => $today->format('Y-m-d'),
            'requested_time' => '11:30:00',
            'confirmed_date' => $today->format('Y-m-d'),
            'confirmed_time' => '11:30:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'patient_notes' => 'Impacted wisdom tooth consultation.',
            'staff_notes' => 'Pre-op instructions communicated to patient.',
        ]);
        $apt3->logActivity('created', null, Appointment::STATUS_PENDING, 'Patient requested appointment.', $patientModels[2]->id);
        $apt3->logActivity('confirmed', Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED, 'Staff confirmed schedule.', $staff->id);

        // Appointment 4: Completed appointment for John Doe (past)
        $apt4 = Appointment::create([
            'appointment_number' => 'APT-' . $pastTwoWeeks->format('Ymd') . '-004',
            'patient_id' => $mainPatient->id,
            'dentist_id' => $dentistModels[2]->id,
            'service_type' => 'Dental Restoration/Filling',
            'requested_date' => $pastTwoWeeks->format('Y-m-d'),
            'requested_time' => '15:00:00',
            'confirmed_date' => $pastTwoWeeks->format('Y-m-d'),
            'confirmed_time' => '15:00:00',
            'status' => Appointment::STATUS_COMPLETED,
            'patient_notes' => 'Composite filling replacement on upper premolar.',
            'staff_notes' => 'Procedure completed without complications.',
        ]);
        $apt4->logActivity('created', null, Appointment::STATUS_PENDING, 'Patient requested appointment.', $mainPatient->id);
        $apt4->logActivity('confirmed', Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED, 'Confirmed appointment.', $staff->id);
        $apt4->logActivity('completed', Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED, 'Marked completed by dentist.', $dentistModels[2]->user_id);

        // Appointment 5: Reschedule requested by Chloe Bennet
        $apt5 = Appointment::create([
            'appointment_number' => 'APT-' . $today->format('Ymd') . '-005',
            'patient_id' => $patientModels[3]->id,
            'dentist_id' => $dentistModels[1]->id,
            'service_type' => 'Teeth Whitening',
            'requested_date' => $today->addDays(3)->format('Y-m-d'),
            'requested_time' => '13:00:00',
            'confirmed_date' => $today->addDays(3)->format('Y-m-d'),
            'confirmed_time' => '13:00:00',
            'status' => Appointment::STATUS_RESCHEDULE_REQUESTED,
            'reschedule_reason' => 'Work schedule conflict on that afternoon; requesting next available morning slot.',
        ]);
        $apt5->logActivity('created', null, Appointment::STATUS_PENDING, 'Initial appointment created.', $patientModels[3]->id);
        $apt5->logActivity('confirmed', Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED, 'Appointment confirmed.', $staff->id);
        $apt5->logActivity('reschedule_requested', Appointment::STATUS_CONFIRMED, Appointment::STATUS_RESCHEDULE_REQUESTED, 'Patient requested reschedule: Work schedule conflict.', $patientModels[3]->id);

        // Appointment 6: Rejected appointment request
        $apt6 = Appointment::create([
            'appointment_number' => 'APT-' . $pastTwoWeeks->format('Ymd') . '-006',
            'patient_id' => $patientModels[1]->id,
            'dentist_id' => $dentistModels[0]->id,
            'service_type' => 'Orthodontic Consultation',
            'requested_date' => $pastTwoWeeks->subDays(2)->format('Y-m-d'),
            'requested_time' => '16:00:00',
            'status' => Appointment::STATUS_REJECTED,
            'rejection_reason' => 'Clinic was closed for annual maintenance on the requested holiday date.',
        ]);
        $apt6->logActivity('created', null, Appointment::STATUS_PENDING, 'Requested slot.', $patientModels[1]->id);
        $apt6->logActivity('rejected', Appointment::STATUS_PENDING, Appointment::STATUS_REJECTED, 'Clinic closed on requested date.', $staff->id);
    }
}
