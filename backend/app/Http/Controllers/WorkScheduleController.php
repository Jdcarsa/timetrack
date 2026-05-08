<?php

namespace App\Http\Controllers;

use App\Models\UserWorkSchedule;
use App\Support\WorkScheduleHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WorkScheduleController extends Controller
{
    // GET /api/work-schedule
    public function show(Request $request)
    {
        if (!Schema::hasTable('user_work_schedules')) {
            return response()->json([
                'user_id' => $request->user()->id,
                'days' => WorkScheduleHelper::defaultWeekTemplate(),
            ]);
        }

        $user = $request->user();
        $this->ensureInitialSchedule($user->id);

        $user->load('workSchedules');
        $days = WorkScheduleHelper::normalizeForResponse($user->workSchedules);

        return response()->json([
            'user_id' => $user->id,
            'days' => $days,
        ]);
    }

    // PUT /api/work-schedule
    public function update(Request $request)
    {
        if (!Schema::hasTable('user_work_schedules')) {
            return response()->json([
                'message' => 'La tabla de horarios no existe aun. Ejecuta migraciones del backend.',
            ], 409);
        }

        $request->validate([
            'days' => 'required|array|size:7',
            'days.*.day_of_week' => 'required|integer|min:0|max:6',
            'days.*.is_working' => 'required|boolean',
            'days.*.start_time' => 'nullable|date_format:H:i',
            'days.*.end_time' => 'nullable|date_format:H:i',
            'days.*.break_minutes' => 'nullable|integer|min:0|max:300',
        ]);

        $days = collect($request->input('days'));
        if ($days->pluck('day_of_week')->unique()->count() !== 7) {
            return response()->json([
                'message' => 'Debes enviar exactamente un registro por cada dia de la semana.',
            ], 422);
        }

        $errors = [];
        $rows = [];
        $now = now();

        foreach ($days as $item) {
            $day = (int) $item['day_of_week'];
            $isWorking = (bool) $item['is_working'];
            $start = $item['start_time'] ?? null;
            $end = $item['end_time'] ?? null;
            $break = (int) ($item['break_minutes'] ?? 0);

            if ($isWorking) {
                if (!$start || !$end) {
                    $errors["days.$day"] = 'Los dias laborales deben tener hora inicio y fin.';
                    continue;
                }

                $startTime = Carbon::createFromFormat('H:i', $start);
                $endTime = Carbon::createFromFormat('H:i', $end);
                $minutes = $endTime->diffInMinutes($startTime, false);

                if ($minutes <= 0) {
                    $errors["days.$day"] = 'La hora fin debe ser mayor a la hora inicio.';
                    continue;
                }

                if ($break >= $minutes) {
                    $errors["days.$day"] = 'El descanso no puede ser mayor o igual a la duracion del turno.';
                    continue;
                }
            } else {
                $start = null;
                $end = null;
                $break = 0;
            }

            $rows[] = [
                'user_id' => $request->user()->id,
                'day_of_week' => $day,
                'is_working' => $isWorking,
                'start_time' => $start,
                'end_time' => $end,
                'break_minutes' => $break,
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Revisa los datos del horario.',
                'errors' => $errors,
            ], 422);
        }

        DB::transaction(function () use ($rows) {
            UserWorkSchedule::upsert(
                $rows,
                ['user_id', 'day_of_week'],
                ['is_working', 'start_time', 'end_time', 'break_minutes', 'updated_at']
            );
        });

        return $this->show($request);
    }

    private function ensureInitialSchedule(int $userId): void
    {
        if (!Schema::hasTable('user_work_schedules')) {
            return;
        }

        $exists = UserWorkSchedule::where('user_id', $userId)->exists();
        if ($exists) return;

        $now = now();
        $rows = collect(WorkScheduleHelper::defaultWeekTemplate())->map(fn($d) => [
            'user_id' => $userId,
            'day_of_week' => $d['day_of_week'],
            'is_working' => $d['is_working'],
            'start_time' => $d['start_time'],
            'end_time' => $d['end_time'],
            'break_minutes' => $d['break_minutes'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        UserWorkSchedule::insert($rows);
    }
}
