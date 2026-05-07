<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'project_id',
        'created_by',
        'title',
        'description',
        'week_start',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'schedule_tasks');
    }
}