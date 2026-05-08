<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeRecord extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'total_hours',
        'hourly_rate_snapshot',
    ];

    protected $casts = [
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    // Relacion: un registro pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor: calcula el total a pagar dinamicamente
    public function getTotalEarningsAttribute(): float
    {
        return round(($this->total_hours ?? 0) * $this->hourly_rate_snapshot, 2);
    }

    // Accessor: retorna total_hours formateado o null
    public function getFormattedHoursAttribute(): ?string
    {
        if (!$this->total_hours) return null;
        $h = floor($this->total_hours);
        $m = round(($this->total_hours - $h) * 60);
        return "{$h}h {$m}m";
    }
    
    // ✅ Nuevo: Obtener clock_in en la zona horaria del usuario
    public function getClockInInTimezone($timezone)
    {
        return $this->clock_in->timezone($timezone);
    }
    
    // ✅ Nuevo: Obtener clock_out en la zona horaria del usuario
    public function getClockOutInTimezone($timezone)
    {
        return $this->clock_out?->timezone($timezone);
    }
}