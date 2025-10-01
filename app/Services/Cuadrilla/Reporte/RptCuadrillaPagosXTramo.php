<?php

namespace App\Services\Cuadrilla\Reporte;

use App\Models\CuadResumenPorTramo;
use App\Services\Cuadrilla\TramoLaboral\ListaAcumuladaTramos;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Cuadrilla\CuadrillaRptPagoExport;
use Carbon\Carbon;

class RptCuadrillaPagosXTramo
{
    /**
     * @var object
     */
    private $grupo;
    /**
     * @var bool
     */
    private $pagarBonos;
    /**
     * @var array
     */
    private $listaPago;
    /**
     * @var string
     */
    private $fechaInicio;
    /**
     * @var string
     */
    private $fechaFin;
    /**
     * @var string
     */
    private $fechaReporte;
    private $resumenTramo;

    public function __construct()
    {
        // El constructor puede permanecer vacío.
    }

    /**
     * Punto de entrada principal para orquestar la preparación de datos y la descarga del reporte.
     *
     * @param array $dataFromController Los datos crudos del controlador.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function descargarReporte(array $dataFromController)
    {
        // 1. Prepara y transforma los datos al formato requerido por el exportador.
        $datosParaExportacion = $this->prepararDatosParaExportacion($dataFromController);
        
        // 2. Genera un nombre de archivo dinámico y descriptivo.
        $nombreGrupo = str_replace(' ', '_', strtoupper($this->resumenTramo->grupo->nombre));
        $fechaArchivo = Carbon::now()->format('Ymd_His');
        $nombreArchivo = "reporte_pagos_{$nombreGrupo}_{$fechaArchivo}.xlsx";

        // 3. Pasa los datos ya transformados al Export y procede con la descarga.
        return Excel::download(new CuadrillaRptPagoExport($datosParaExportacion), $nombreArchivo);
    }

    /**
     * Transforma los datos del controlador a la estructura final requerida por la vista de Excel.
     *
     * @param array $data
     * @return array
     */
    private function prepararDatosParaExportacion(array $data): array
    {
        $this->inicializarPropiedades($data);
        
        $tramos = app(ListaAcumuladaTramos::class)->obtenerPagoCuadrillerosPorTramo(
            $data['resumen_tramo'],
            $data['lista_pago']
        );
        $bonos = $this->generarListaBonos();
        
        $totalBono = array_sum(array_column($bonos, 'bono'));

        // Formatear fechas para los títulos
        Carbon::setLocale('es'); // Asegura que los nombres de los meses estén en español
        $formatoFechaTitulo = 'd \d\e F';
        $fechaInicioTitulo = Carbon::parse($this->fechaInicio)->translatedFormat($formatoFechaTitulo);
        $fechaFinTitulo = Carbon::parse($this->resumenTramo->fecha_fin)->translatedFormat($formatoFechaTitulo);
        $nombreGrupo = mb_strtoupper($this->resumenTramo->grupo->nombre);

        return [
            'tramos' => $tramos,
            'bonos' => $bonos,
            'total_bono' => $totalBono,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->resumenTramo->fecha_fin,
            'fecha_reporte' => $this->fechaReporte,
            'titulo' => mb_strtoupper("CUADRILLA {$nombreGrupo} DEL {$fechaInicioTitulo} AL {$fechaFinTitulo}"),
            'titulo_bono' => mb_strtoupper("BONO CUADRILLA {$nombreGrupo}"),
            'titulo_consolidado' => mb_strtoupper("{$nombreGrupo} - CONSOLIDADO DEL {$fechaInicioTitulo} AL {$fechaFinTitulo}"),
        ];
    }

    /**
     * Asigna los datos del controlador a las propiedades de la clase para un acceso más fácil.
     */
    private function inicializarPropiedades(array $data): void
    {
        $this->resumenTramo = $data['resumen_tramo'];
        $this->listaPago = $data['lista_pago'];
        $this->fechaInicio = $data['fecha_inicio'];
        $this->fechaReporte = $data['fecha_reporte'];
    }

    /**
     * Recupera los tramos encadenados hacia atrás desde el resumen actual
     * y devuelve la lista de tramos con pagos.
     *
     * @return array
     */
    

    /**
     * Genera la lista de bonos a partir de la lista de pagos si la opción está activada.
     */
    private function generarListaBonos(): array
    {
        return collect($this->listaPago)->filter(function ($trabajador) {
            return isset($trabajador['bono']) && $trabajador['bono'] > 0;
        })->map(function ($trabajador) {
            return [
                'nombre' => $trabajador['nombres'],
                'bono' => (float) $trabajador['bono']
            ];
        })->values()->all();
    }
}