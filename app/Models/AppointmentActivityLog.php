<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'user_id',
        'action',
        'previous_status',
        'new_status',
        'details',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
