<?php

namespace App\Livewire\Traits;

use App\Services\Reporte\RegistroDiario\ReporteRegistroDiarioExcel;

/**
 * Botón "Enviar reporte diario" de los registros diarios de planilla, cuadrilla y riego: descarga el Excel
 * del día seleccionado ($fecha del componente) con lo registrado hasta ese momento en los tres.
 */
trait ConReporteDiarioExcel
{
    public function descargarReporteDiario()
    {
        try {
            $archivo = app(ReporteRegistroDiarioExcel::class)->generar($this->fecha);
            return response()->download($archivo['ruta'], $archivo['nombre'])->deleteFileAfterSend();
        } catch (\Throwable $e) {
            report($e);
            $this->alert('error', 'No se pudo generar el reporte diario: ' . $e->getMessage());
        }
    }
}
