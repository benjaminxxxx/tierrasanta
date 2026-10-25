<?php

namespace App\Livewire\Traits;

use App\Models\ConsolidadoRiego;
use App\Models\CuadRegistroDiario;
use App\Models\PlanRegistroDiario;
use App\Services\Reporte\RegistroDiario\ReporteRegistroDiarioExcel;

/**
 * Botón "Enviar reporte diario" de los registros diarios de planilla, cuadrilla y riego: descarga el Excel
 * del día seleccionado ($fecha del componente) con lo registrado hasta ese momento en los tres. Sin nada registrado
 * ese día no se genera (la vista puede desactivar el botón con hayRegistrosDelDia()).
 */
trait ConReporteDiarioExcel
{
    public function descargarReporteDiario()
    {
        if (!$this->hayRegistrosDelDia()) {
            $this->alert('warning', 'No hay nada registrado el ' . \Illuminate\Support\Carbon::parse($this->fecha)->format('d/m/Y')
                . ' en planilla, cuadrilla ni riego: no hay reporte que enviar.');
            return null;
        }
        try {
            $archivo = app(ReporteRegistroDiarioExcel::class)->generar($this->fecha);
            return response()->download($archivo['ruta'], $archivo['nombre'])->deleteFileAfterSend();
        } catch (\Throwable $e) {
            report($e);
            $this->errorAlert($e, 'No se pudo generar el reporte diario');
        }
    }

    /** ¿Hay algo registrado ese día en planilla, cuadrilla o riego? */
    public function hayRegistrosDelDia(): bool
    {
        if (!$this->fecha) {
            return false;
        }
        return PlanRegistroDiario::whereDate('fecha', $this->fecha)->exists()
            || CuadRegistroDiario::whereDate('fecha', $this->fecha)->exists()
            || ConsolidadoRiego::whereDate('fecha', $this->fecha)->exists();
    }
}
