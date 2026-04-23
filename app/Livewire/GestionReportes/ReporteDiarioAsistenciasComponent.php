<?php

namespace App\Livewire\GestionReportes;

use App\Models\PlanResumenDiario;
use Livewire\Component;

class ReporteDiarioAsistenciasComponent extends Component
{
    public string $fecha;

    public ?PlanResumenDiario $resumen = null;
    public array $totales = [];
    public int $totalPlanilla = 0;
    public bool $sinDatos = false;

    public function mount(string $fecha): void
    {
        $this->fecha = $fecha;
        $this->cargarDatos();
    }

    public function actualizar(): void
    {
        $this->cargarDatos();
        $this->dispatch('resumenActualizado', totales: $this->totales);
    }

    private function cargarDatos(): void
    {
        $this->resumen = PlanResumenDiario::with('totales')
            ->whereDate('fecha', $this->fecha)
            ->first();

        if (!$this->resumen) {
            $this->sinDatos = true;
            $this->totales = [];
            $this->totalPlanilla = 0;
            return;
        }

        $this->sinDatos = false;
        $this->totalPlanilla = $this->resumen->total_planilla ?? 0;

        $this->totales = $this->resumen->totales
            ->sortByDesc('total_asistidos')
            ->map(function ($t) {
                $porcentaje = $this->totalPlanilla > 0
                    ? round(($t->total_asistidos / $this->totalPlanilla) * 100, 1)
                    : 0;

                return [
                    'codigo'        => $t->codigo,
                    'descripcion'   => $t->descripcion,
                    'color'         => $t->color,
                    'tipo'          => $t->tipo,
                    'total'         => $t->total_asistidos,
                    'porcentaje'    => $porcentaje,
                    'acumula'       => $t->acumula_asistencia,
                    'afecta_sueldo' => $t->afecta_sueldo,
                ];
            })
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.gestion-reportes.reporte-diario-asistencias-component');
    }
}