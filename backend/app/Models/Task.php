<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'date',
        'start_time',
        'end_time',
        'status',
        'is_recurring',
        'recur_day',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_recurring' => 'boolean',
    ];

    // Relación: una tarea pertenece a un empleado
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor: devuelve el nombre del día en español
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Lunes', 1 => 'Martes', 2 => 'Miércoles',
            3 => 'Jueves', 4 => 'Viernes', 5 => 'Sábado', 6 => 'Domingo'
        ];
        return $days[$this->recur_day] ?? '—';
    }

    // Accessor: etiqueta del estado en español
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'Pendiente',
            'in_progress' => 'En progreso',
            'completed'   => 'Completada',
            default       => $this->status,
        };
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_tasks');
    }
}