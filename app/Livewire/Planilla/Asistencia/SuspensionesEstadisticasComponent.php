<?php

namespace App\Livewire\Planilla\Asistencia;

use App\Services\Planilla\Suspension\PlanillaSuspensionConsulta;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Gráfico e historial de suspensiones (pestaña Estadísticas de Permisos y suspensiones).
 *
 * Recibe los filtros del padre y se crea de nuevo cada vez que cambian (wire:key con los filtros): así el gráfico se
 * dibuja una sola vez sobre un canvas nuevo, sin eventos ni morph de Livewire encima de Chart.js.
 */
class SuspensionesEstadisticasComponent extends Component
{
    public int $anio;
    public ?int $mes = null;
    public ?int $empleadoId = null;
    public ?string $tipoPlanilla = null;

    public function render()
    {
        $consulta = app(PlanillaSuspensionConsulta::class);
        $grafico = $this->mes
            ? $consulta->mensual($this->mes, $this->anio, $this->tipoPlanilla, $this->empleadoId)
            : $consulta->anual($this->anio, $this->tipoPlanilla, $this->empleadoId);

        return view('livewire.planilla.asistencia.suspensiones-estadisticas-component', [
            'grafico' => $grafico,
            'titulo' => $this->mes
                ? 'Trabajadores suspendidos por día · ' . ucfirst(Carbon::create($this->anio, $this->mes, 1)->translatedFormat('F Y'))
                : "Días de suspensión por mes · {$this->anio}",
            'historial' => $this->empleadoId ? $consulta->historial($this->empleadoId) : null,
        ]);
    }
}
