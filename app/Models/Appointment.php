<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $guarded = ['id'];

    public const STATUSES = ['pending', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'rejected'];

    protected function casts(): array
    {
        return ['preferred_date' => 'date:Y-m-d', 'consent' => 'boolean', 'consented_at' => 'datetime'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function history()
    {
        return $this->hasMany(AppointmentStatusHistory::class)->orderByDesc('id');
    }
}
