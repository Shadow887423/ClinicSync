<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreatmentRecord extends Model
{
    use HasFactory, SoftDeletes, Auditable;

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
        'deletion_reason',
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
