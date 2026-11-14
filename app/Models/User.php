<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Auditable;

    const ROLE_PATIENT = 'patient';
    const ROLE_STAFF = 'staff';
    const ROLE_ADMIN = 'admin';
    const ROLE_DENTIST = 'dentist';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'birth_date',
        'gender',
        'password',
        'deletion_reason',
    ];

    protected $attributes = [
        'role' => self::ROLE_PATIENT,
        'status' => 'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
    ];

    public function isPatient(): bool
    {
        return $this->role === self::ROLE_PATIENT;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDentist(): bool
    {
        return $this->role === self::ROLE_DENTIST;
    }

    public function isStaffOrAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_STAFF, self::ROLE_ADMIN]);
    }

    public function dentist()
    {
        return $this->hasOne(Dentist::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function smsNotifications()
    {
        return $this->hasMany(SmsNotification::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(AppointmentActivityLog::class);
    }

    public function medicalHistory()
    {
        return $this->hasOne(PatientMedicalHistory::class);
    }

    public function treatmentRecords()
    {
        return $this->hasMany(TreatmentRecord::class, 'patient_id');
    }
}
