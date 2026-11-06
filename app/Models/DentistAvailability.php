<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DentistAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'dentist_id',
        'day_of_week',
        'day_name',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'status',
    ];

    public function dentist()
    {
        return $this->belongsTo(Dentist::class);
    }
}
