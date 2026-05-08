<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWorkSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'is_working',
        'start_time',
        'end_time',
        'break_minutes',
    ];

    protected $casts = [
        'is_working' => 'boolean',
        'day_of_week' => 'integer',
        'break_minutes' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
