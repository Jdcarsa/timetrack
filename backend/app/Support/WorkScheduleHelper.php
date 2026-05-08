<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkScheduleHelper
{
    public static function defaultWeekTemplate(): array
    {
        return [
            ['day_of_week' => 0, 'name' => 'Lunes',    'is_working' => true,  'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60],
            ['day_of_week' => 1, 'name' => 'Martes',   'is_working' => true,  'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60],
            ['day_of_week' => 2, 'name' => 'Miercoles','is_working' => true,  'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60],
            ['day_of_week' => 3, 'name' => 'Jueves',   'is_working' => true,  'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60],
            ['day_of_week' => 4, 'name' => 'Viernes',  'is_working' => true,  'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60],
            ['day_of_week' => 5, 'name' => 'Sabado',   'is_working' => false, 'start_time' => null,    'end_time' => null,    'break_minutes' => 0],
            ['day_of_week' => 6, 'name' => 'Domingo',  'is_working' => false, 'start_time' => null,    'end_time' => null,    'break_minutes' => 0],
        ];
    }

    public static function normalizeForResponse(Collection $schedules): array
    {
        $byDay = $schedules->keyBy('day_of_week');
        $base = collect(self::defaultWeekTemplate())->map(function ($day) use ($byDay) {
            $current = $byDay->get($day['day_of_week']);
            if (!$current) {
                return $day;
            }

            return [
                'day_of_week' => (int) $current->day_of_week,
                'name' => $day['name'],
                'is_working' => (bool) $current->is_working,
                'start_time' => $current->start_time ? substr((string) $current->start_time, 0, 5) : null,
                'end_time' => $current->end_time ? substr((string) $current->end_time, 0, 5) : null,
                'break_minutes' => (int) ($current->break_minutes ?? 0),
            ];
        });

        return $base->values()->all();
    }

    public static function toMap(Collection $schedules): array
    {
        $normalized = self::normalizeForResponse($schedules);
        $map = [];
        foreach ($normalized as $day) {
            $map[$day['day_of_week']] = $day;
        }
        return $map;
    }

    public static function dayIndexFromDate(Carbon $date): int
    {
        // dayOfWeekIso: 1=Lunes ... 7=Domingo
        return $date->dayOfWeekIso - 1;
    }

    public static function dayMinutes(array $day): int
    {
        if (empty($day['is_working']) || empty($day['start_time']) || empty($day['end_time'])) {
            return 0;
        }

        $start = Carbon::createFromFormat('H:i', substr((string) $day['start_time'], 0, 5));
        $end = Carbon::createFromFormat('H:i', substr((string) $day['end_time'], 0, 5));

        $minutes = $end->diffInMinutes($start, false);
        if ($minutes <= 0) {
            return 0;
        }

        $break = (int) ($day['break_minutes'] ?? 0);
        return max(0, $minutes - $break);
    }

    public static function plannedMinutesInRange(array $scheduleMap, Carbon $from, Carbon $to): array
    {
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $totalMinutes = 0;
        $plannedDays = 0;

        while ($cursor->lte($end)) {
            $dayIndex = self::dayIndexFromDate($cursor);
            $minutes = self::dayMinutes($scheduleMap[$dayIndex] ?? []);
            if ($minutes > 0) {
                $plannedDays++;
                $totalMinutes += $minutes;
            }
            $cursor->addDay();
        }

        return [
            'minutes' => $totalMinutes,
            'days' => $plannedDays,
        ];
    }
}
