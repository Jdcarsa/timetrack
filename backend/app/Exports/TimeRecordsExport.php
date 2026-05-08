<?php

namespace App\Exports;

use App\Models\TimeRecord;
use App\Models\User;
use App\Models\UserWorkSchedule;
use App\Support\WorkScheduleHelper;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Schema;

class TimeRecordsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnWidths
{
    public function __construct(
        private string $from,
        private string $to,
        private ?int   $userId = null,
        private string $mode = 'actual'
    ) {}

    public function collection()
    {
        if ($this->mode === 'schedule') {
            return $this->scheduledCollection();
        }

        return $this->actualCollection();
    }

    private function actualCollection()
    {
        $query = TimeRecord::with('user')
            ->whereBetween('clock_in', [
                $this->from . ' 00:00:00',
                $this->to   . ' 23:59:59',
            ])
            ->orderBy('user_id')
            ->orderBy('clock_in');

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        return $query->get()->map(function ($record) {
            return [
                'employee' => $record->user->name,
                'email' => $record->user->email,
                'date' => $record->clock_in->format('d/m/Y'),
                'clock_in' => $record->clock_in->format('H:i'),
                'clock_out' => $record->clock_out?->format('H:i') ?? '-',
                'total_hours' => $record->total_hours !== null ? number_format($record->total_hours, 2) : '-',
                'hourly_rate' => number_format((float) $record->hourly_rate_snapshot, 2),
                'total_earnings' => $record->clock_out ? number_format((float) $record->total_earnings, 2) : '-',
                'status' => $record->clock_out ? 'Completado' : 'Sin salida',
                'source' => 'Horas contadas',
            ];
        });
    }

    private function scheduledCollection()
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->startOfDay();

        $hasWorkSchedulesTable = Schema::hasTable('user_work_schedules');

        $users = User::when($hasWorkSchedulesTable, fn($q) => $q->with('workSchedules'))
            ->where('role', 'employee')
            ->when($this->userId, fn($q) => $q->where('id', $this->userId))
            ->orderBy('name')
            ->get();

        $rows = collect();

        foreach ($users as $user) {
            if ($hasWorkSchedulesTable) {
                $this->ensureInitialSchedule($user->id);
                $user->load('workSchedules');
                $map = WorkScheduleHelper::toMap($user->workSchedules);
            } else {
                $map = collect(WorkScheduleHelper::defaultWeekTemplate())->keyBy('day_of_week')->all();
            }

            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $dayIndex = WorkScheduleHelper::dayIndexFromDate($cursor);
                $day = $map[$dayIndex] ?? null;
                $minutes = $day ? WorkScheduleHelper::dayMinutes($day) : 0;

                if ($minutes > 0) {
                    $hours = round($minutes / 60, 2);
                    $rows->push([
                        'employee' => $user->name,
                        'email' => $user->email,
                        'date' => $cursor->format('d/m/Y'),
                        'clock_in' => $day['start_time'],
                        'clock_out' => $day['end_time'],
                        'total_hours' => number_format($hours, 2),
                        'hourly_rate' => number_format((float) $user->hourly_rate, 2),
                        'total_earnings' => number_format($hours * (float) $user->hourly_rate, 2),
                        'status' => 'Planificado',
                        'source' => 'Horario',
                    ]);
                }

                $cursor->addDay();
            }
        }

        return $rows;
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

    public function headings(): array
    {
        return [
            'Empleado',
            'Email',
            'Fecha',
            'Hora Entrada',
            'Hora Salida',
            'Horas',
            'Valor Hora ($)',
            'Total a Pagar ($)',
            'Estado',
            'Fuente',
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee'],
            $row['email'],
            $row['date'],
            $row['clock_in'],
            $row['clock_out'],
            $row['total_hours'],
            $row['hourly_rate'],
            $row['total_earnings'],
            $row['status'],
            $row['source'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 28,
            'C' => 14,
            'D' => 14,
            'E' => 14,
            'F' => 12,
            'G' => 16,
            'H' => 18,
            'I' => 14,
            'J' => 14,
        ];
    }

    public function title(): string
    {
        $modeLabel = $this->mode === 'schedule' ? 'Horario' : 'Horas';
        return "Registros {$modeLabel} {$this->from} - {$this->to}";
    }
}
