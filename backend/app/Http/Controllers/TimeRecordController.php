<?php

namespace App\Http\Controllers;

use App\Models\TimeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // ✅ Guardar en UTC
        $record = TimeRecord::create([
            'user_id'              => $user->id,
            'clock_in'             => now()->utc(),  // ← Siempre UTC
            'hourly_rate_snapshot' => $user->hourly_rate,
        ]);

        // Obtener zona horaria del usuario para la respuesta
        $userTimezone = $user->timezone ?? 'UTC';

        return response()->json([
            'message' => 'Entrada registrada correctamente.',
            'record'  => [
                'id' => $record->id,
                'clock_in' => $record->clock_in->timezone($userTimezone)->toDateTimeString(),
                'clock_in_utc' => $record->clock_in->toDateTimeString(), // Opcional: para debug
            ],
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
    
        // ✅ Guardar en UTC
        $clockOut = now()->utc();  // ← Siempre UTC
        
        // ✅ Fórmula corregida: clock_in -> clock_out
        $diffInMinutes = $record->clock_in->diffInMinutes($clockOut);
        $totalHours = $diffInMinutes / 60;
        
        // Log para debug (opcional)
        Log::info('Clock-out calculado', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'clock_in_utc' => $record->clock_in->toDateTimeString(),
            'clock_out_utc' => $clockOut->toDateTimeString(),
            'diff_minutes' => $diffInMinutes,
            'total_hours' => round($totalHours, 2),
            'hourly_rate' => $record->hourly_rate_snapshot,
        ]);
    
        $record->update([
            'clock_out'   => $clockOut,
            'total_hours' => round($totalHours, 2),
        ]);
        
        // Obtener zona horaria del usuario para la respuesta
        $userTimezone = $user->timezone ?? 'UTC';
        
        return response()->json([
            'message' => 'Salida registrada correctamente.',
            'record'  => [
                'id' => $record->id,
                'clock_in' => $record->clock_in->timezone($userTimezone)->toDateTimeString(),
                'clock_out' => $record->clock_out->timezone($userTimezone)->toDateTimeString(),
                'total_hours' => $record->total_hours,
                'hourly_rate_snapshot' => $record->hourly_rate_snapshot,
            ],
            'total_hours' => round($totalHours, 2),
            'total_earnings' => round($totalHours * $record->hourly_rate_snapshot, 2),
        ]);
    }

    // GET /api/records — Historial del empleado autenticado
    public function myRecords(Request $request)
    {
        $user = $request->user();
        $userTimezone = $user->timezone ?? 'UTC';
        
        $records = TimeRecord::where('user_id', $user->id)
            ->orderBy('clock_in', 'desc')
            ->paginate(15);
        
        // Convertir las fechas a la zona horaria del usuario
        $records->getCollection()->transform(function ($record) use ($userTimezone) {
            return [
                'id' => $record->id,
                'clock_in' => $record->clock_in->timezone($userTimezone)->toDateTimeString(),
                'clock_in_utc' => $record->clock_in->toDateTimeString(), // Opcional
                'clock_out' => $record->clock_out?->timezone($userTimezone)->toDateTimeString(),
                'clock_out_utc' => $record->clock_out?->toDateTimeString(), // Opcional
                'total_hours' => $record->total_hours,
                'hourly_rate_snapshot' => $record->hourly_rate_snapshot,
                'total_earnings' => $record->total_earnings,
                'created_at' => $record->created_at->timezone($userTimezone)->toDateTimeString(),
            ];
        });
        
        return response()->json($records);
    }

    // GET /api/status — Estado actual (si tiene entrada abierta)
    public function status(Request $request)
    {
        $user = $request->user();
        $userTimezone = $user->timezone ?? 'UTC';
        
        $open = TimeRecord::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->first();

        return response()->json([
            'is_clocked_in' => !is_null($open),
            'record' => $open ? [
                'id' => $open->id,
                'clock_in' => $open->clock_in->timezone($userTimezone)->toDateTimeString(),
                'clock_in_utc' => $open->clock_in->toDateTimeString(),
                'hourly_rate_snapshot' => $open->hourly_rate_snapshot,
            ] : null,
        ]);
    }
}