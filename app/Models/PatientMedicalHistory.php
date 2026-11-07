<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientMedicalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'has_hypertension',
        'has_diabetes',
        'has_heart_condition',
        'has_bleeding_disorder',
        'has_allergies',
        'allergy_details',
        'current_medications',
        'past_surgeries',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'has_hypertension' => 'boolean',
        'has_diabetes' => 'boolean',
        'has_heart_condition' => 'boolean',
        'has_bleeding_disorder' => 'boolean',
        'has_allergies' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if patient has any high-risk medical alerts that dentists must be aware of
     */
    public function hasCriticalAlerts(): bool
    {
        return $this->has_hypertension
            || $this->has_diabetes
            || $this->has_heart_condition
            || $this->has_bleeding_disorder
            || $this->has_allergies;
    }

    /**
     * Get a list of alert badges for clinical display
     */
    public function getActiveAlerts(): array
    {
        $alerts = [];
        if ($this->has_allergies) {
            $alerts[] = 'Allergies: ' . ($this->allergy_details ?: 'Yes');
        }
        if ($this->has_bleeding_disorder) {
            $alerts[] = 'Bleeding Disorder';
        }
        if ($this->has_heart_condition) {
            $alerts[] = 'Cardiovascular Condition';
        }
        if ($this->has_hypertension) {
            $alerts[] = 'Hypertension';
        }
        if ($this->has_diabetes) {
            $alerts[] = 'Diabetes';
        }
        return $alerts;
    }
}
