<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'hourly_rate',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'hourly_rate'       => 'decimal:2',
    ];

    // Relacion: un usuario tiene muchos registros de tiempo
    public function timeRecords()
    {
        return $this->hasMany(TimeRecord::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

        public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_users');
    }

    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'created_by');
    }
}
