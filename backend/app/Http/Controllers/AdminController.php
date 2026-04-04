<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TimeRecord;
use App\Exports\TimeRecordsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    // GET /api/admin/employees — Lista todos los empleados
    public function employees()
    {
        $employees = User::where('role', 'employee')
            ->select('id', 'name', 'email', 'hourly_rate')
            ->orderBy('name')
            ->get();

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
        ]);

        $filename = 'timetrack_' . $request->from . '_' . $request->to . '.xlsx';

        return Excel::download(
            new TimeRecordsExport($request->from, $request->to, $request->user_id),
            $filename
        );
    }

    // GET /api/admin/summary — Resumen por empleado en rango de fechas
    public function summary(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $records = TimeRecord::with('user:id,name,email,hourly_rate')
            ->whereBetween('clock_in', [$request->from . ' 00:00:00', $request->to . ' 23:59:59'])
            ->whereNotNull('clock_out')
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

        return response()->json([
            'message' => 'Empleado creado correctamente.',
            'user'    => $user->only(['id', 'name', 'email', 'hourly_rate']),
        ], 201);
    }
}
