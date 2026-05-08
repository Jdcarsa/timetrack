<?php

namespace App\Http\Controllers;

use App\Models\TimeRecord;
use Illuminate\Http\Request;

class TimeRecordController extends Controller
{
    // POST /api/clock-in — El empleado marca entrada
    public function clockIn(Request $request)
    {
        $user = $request->user();

        // Verifica que no tenga una sesion abierta sin cerrar
        $open = TimeRecord::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->first();

        if ($open) {
            return response()->json([
                'message' => 'Ya tienes una entrada activa. Debes marcar salida primero.',
                'record'  => $open,
            ], 422);
        }

        $record = TimeRecord::create([
            'user_id'              => $user->id,
            'clock_in'             => now(),
            'hourly_rate_snapshot' => $user->hourly_rate, // Se guarda la tarifa actual
        ]);

        return response()->json([
            'message' => 'Entrada registrada correctamente.',
            'record'  => $record,
        ], 201);
    }

    // POST /api/clock-out — El empleado marca salida
    public function clockOut(Request $request)
    {
        $user = $request->user();
    
        $record = TimeRecord::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();
    
        if (!$record) {
            return response()->json([
                'message' => 'No tienes una entrada activa para registrar salida.',
            ], 422);
        }
    
        $clockOut = now();
        
        $diffInMinutes = $record->clock_in->diffInMinutes($clockOut);
        
        $totalHours = $diffInMinutes / 60;
    
        $record->update([
            'clock_out'   => $clockOut,
            'total_hours' => round($totalHours, 2),
        ]);
        
        return response()->json([
            'message' => 'Salida registrada correctamente.',
            'record'  => $record,
            'total_hours'   => round($totalHours, 2),
            'total_earnings' => round($totalHours * $record->hourly_rate_snapshot, 2),
        ]);
    }

    // GET /api/records — Historial del empleado autenticado
    public function myRecords(Request $request)
    {
        $records = TimeRecord::where('user_id', $request->user()->id)
            ->orderBy('clock_in', 'desc')
            ->paginate(15);

        return response()->json($records);
    }

    // GET /api/status — Estado actual (si tiene entrada abierta)
    public function status(Request $request)
    {
        $open = TimeRecord::where('user_id', $request->user()->id)
            ->whereNull('clock_out')
            ->first();

        return response()->json([
            'is_clocked_in' => !is_null($open),
            'record'        => $open,
        ]);
    }
}
