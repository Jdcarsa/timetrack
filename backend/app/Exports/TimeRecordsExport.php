<?php

namespace App\Exports;

use App\Models\TimeRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        private ?int   $userId = null
    ) {}

    public function collection()
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

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Empleado',
            'Email',
            'Fecha',
            'Hora Entrada',
            'Hora Salida',
            'Horas Trabajadas',
            'Valor Hora ($)',
            'Total a Pagar ($)',
            'Estado',
        ];
    }

    public function map($record): array
    {
        return [
            $record->user->name,
            $record->user->email,
            $record->clock_in->format('d/m/Y'),
            $record->clock_in->format('H:i:s'),
            $record->clock_out?->format('H:i:s') ?? '—',
            $record->total_hours ? number_format($record->total_hours, 2) : '—',
            number_format($record->hourly_rate_snapshot, 2),
            $record->clock_out ? number_format($record->total_earnings, 2) : '—',
            $record->clock_out ? 'Completado' : 'Sin salida',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Fila de encabezados en negrita con fondo azul
            1 => [
                'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'    => ['fillType' => 'solid', 'startColor' => ['rgb' => '2563EB']],
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
            'F' => 18,
            'G' => 16,
            'H' => 16,
            'I' => 14,
        ];
    }

    public function title(): string
    {
        return "Registros {$this->from} - {$this->to}";
    }
}
