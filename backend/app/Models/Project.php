<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_users');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'in_progress' => 'En progreso',
            'completed'   => 'Completado',
            default       => $this->status,
        };
    }
}