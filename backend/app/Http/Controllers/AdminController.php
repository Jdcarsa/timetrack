<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TimeRecord;
use App\Models\UserWorkSchedule;
use App\Exports\TimeRecordsExport;
use App\Support\WorkScheduleHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    // GET /api/admin/employees — Lista todos los empleados
    public function employees()
    {
        $hasWorkSchedulesTable = Schema::hasTable('user_work_schedules');

        $query = User::where('role', 'employee')
            ->select('id', 'name', 'email', 'hourly_rate')
            ->orderBy('name');

        if ($hasWorkSchedulesTable) {
            $query->with('workSchedules');
        }

        $employees = $query->get()->map(function ($employee) use ($hasWorkSchedulesTable) {
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'hourly_rate' => $employee->hourly_rate,
                'work_schedule' => $hasWorkSchedulesTable
                    ? WorkScheduleHelper::normalizeForResponse($employee->workSchedules)
                    : WorkScheduleHelper::defaultWeekTemplate(),
            ];
        });

        return response()->json($employees);
    }

    // PUT /api/admin/employees/{id}/hourly-rate — Actualiza tarifa de un empleado
    public function updateHourlyRate(Request $request, User $user)
    {
        $request->validate([
            'hourly_rate' => 'required|numeric|min:0',
        ]);

        if ($user->isAdmin()) {
            return response()->json(['message' => 'No se puede modificar la tarifa de un admin.'], 422);
        }

        $user->update(['hourly_rate' => $request->hourly_rate]);

        return response()->json([
            'message' => 'Tarifa actualizada correctamente.',
            'user'    => $user->only(['id', 'name', 'email', 'hourly_rate']),
        ]);
    }

    // GET /api/admin/records — Todos los registros con filtro opcional por fecha y empleado
    public function allRecords(Request $request)
    {
        $query = TimeRecord::with('user:id,name,email')
            ->orderBy('clock_in', 'desc');

        if ($request->filled('from')) {
            $query->where('clock_in', '>=', $request->from . ' 00:00:00');
        }
        if ($request->filled('to')) {
            $query->where('clock_in', '<=', $request->to . ' 23:59:59');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $records = $query->paginate(20);

        return response()->json($records);
    }

    // GET /api/admin/export — Exporta a Excel
    public function export(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
            'mode' => 'nullable|in:actual,schedule',
        ]);

        $filename = 'timetrack_' . $request->from . '_' . $request->to . '.xlsx';
        $mode = $request->input('mode', 'actual');

        return Excel::download(
            new TimeRecordsExport($request->from, $request->to, $request->user_id, $mode),
            $filename
        );
    }

    // GET /api/admin/summary — Resumen por empleado en rango de fechas
    public function summary(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
            'mode' => 'nullable|in:actual,schedule',
        ]);

        $mode = $request->input('mode', 'actual');

        if ($mode === 'schedule') {
            return response()->json($this->scheduleSummary($request));
        }

        $records = TimeRecord::with('user:id,name,email,hourly_rate')
            ->whereBetween('clock_in', [$request->from . ' 00:00:00', $request->to . ' 23:59:59'])
            ->whereNotNull('clock_out')
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->get();

        // Agrupa por empleado
        $summary = $records->groupBy('user_id')->map(function ($userRecords) {
            $user        = $userRecords->first()->user;
            $totalHours  = $userRecords->sum('total_hours');
            $totalEarnings = $userRecords->sum(fn($r) => $r->total_earnings);

            return [
                'user_id'        => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'total_records'  => $userRecords->count(),
                'total_hours'    => round($totalHours, 2),
                'total_earnings' => round($totalEarnings, 2),
            ];
        })->values();

        return response()->json($summary);
    }

    // POST /api/admin/employees — Crear nuevo empleado
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'hourly_rate' => 'required|numeric|min:0',
        ]);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => bcrypt($request->password),
            'role'        => 'employee',
            'hourly_rate' => $request->hourly_rate,
        ]);

        $this->ensureInitialSchedule($user->id);

        return response()->json([
            'message' => 'Empleado creado correctamente.',
            'user'    => $user->only(['id', 'name', 'email', 'hourly_rate']),
        ], 201);
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

    private function scheduleSummary(Request $request)
    {
        $from = Carbon::parse($request->from)->startOfDay();
        $to = Carbon::parse($request->to)->startOfDay();

        $hasWorkSchedulesTable = Schema::hasTable('user_work_schedules');

        $users = User::when($hasWorkSchedulesTable, fn($q) => $q->with('workSchedules'))
            ->where('role', 'employee')
            ->when($request->filled('user_id'), fn($q) => $q->where('id', $request->user_id))
            ->orderBy('name')
            ->get();

        $summary = $users->map(function (User $user) use ($from, $to, $hasWorkSchedulesTable) {
            $scheduleMap = [];
            if ($hasWorkSchedulesTable) {
                $this->ensureInitialSchedule($user->id);
                $user->load('workSchedules');
                $scheduleMap = WorkScheduleHelper::toMap($user->workSchedules);
            } else {
                $default = collect(WorkScheduleHelper::defaultWeekTemplate());
                $scheduleMap = $default->keyBy('day_of_week')->all();
            }

            $calc = WorkScheduleHelper::plannedMinutesInRange($scheduleMap, $from, $to);
            $hours = round($calc['minutes'] / 60, 2);
            $earnings = round($hours * (float) $user->hourly_rate, 2);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_records' => $calc['days'],
                'total_hours' => $hours,
                'total_earnings' => $earnings,
            ];
        })->filter(fn($row) => $row['total_hours'] > 0)->values();

        return $summary;
    }
}
