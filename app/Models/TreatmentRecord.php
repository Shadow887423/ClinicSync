<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'dentist_id',
        'patient_id',
        'chief_complaint',
        'diagnosis',
        'teeth_numbers',
        'procedure_performed',
        'clinical_notes',
        'prescription',
        'fee_amount',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
