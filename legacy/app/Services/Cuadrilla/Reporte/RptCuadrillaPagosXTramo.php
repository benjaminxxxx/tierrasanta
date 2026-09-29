<?php

namespace App\Services\Cuadrilla\Reporte;

use App\Models\CuadResumenPorTramo;
use App\Services\Cuadrilla\TramoLaboral\ListaAcumuladaTramos;
use Illuminate\Support\Str;
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
     */
    public function generarReporte(array $dataFromController)
    {
        // 1. Prepara y transforma los datos al formato requerido por el exportador.
        $datosParaExportacion = $this->prepararDatosParaExportacion($dataFromController);

        // 2. Genera un nombre de archivo dinámico y descriptivo.
        $nombreGrupo = str_replace(' ', '_', strtoupper($this->resumenTramo->grupo->nombre));
        $fechaArchivo = Carbon::now()->format('Ymd_His');
        $codigoUnico = strtoupper(Str::random(7)); // ejemplo: ABC1234

        // Carpeta: año/mes
        $carpeta = Carbon::now()->format('Y/m');

        // Nombre del archivo
        $nombreArchivo = mb_strtoupper("REPORTE_PAGO_{$nombreGrupo}_{$fechaArchivo}_{$codigoUnico}") . ".xlsx";

        // Ruta relativa dentro de storage/app/public
        $rutaRelativa = "{$carpeta}/{$nombreArchivo}";

        // 3. Guardar el archivo en el disco 'public'.
        Excel::store(new CuadrillaRptPagoExport($datosParaExportacion), $rutaRelativa, 'public');

        // 4. Retornar la ruta para que el caller la guarde en DB
        return $rutaRelativa;
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
        $adicionales = $this->generarListaAdicionales();

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
            'adicionales' => $adicionales,
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
        return collect($this->listaPago)
            ->map(function ($trabajador) {
                // sumar todos los bonos pagados en las fechas
                $totalBono = collect($trabajador)
                    ->filter(fn($valores, $clave) => is_array($valores)) // solo fechas
                    ->reduce(function ($carry, $valores) {
                    $bono = (float) ($valores['total_bono'] ?? 0);
                    $pagado = filter_var($valores['bono_esta_pagado'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    return $carry + ($pagado ? $bono : 0);
                }, 0);

                return [
                    'nombre' => $trabajador['nombres'],
                    'bono' => $totalBono,
                ];
            })
            ->filter(fn($t) => $t['bono'] > 0) // solo los que tienen bono > 0
            ->values()
            ->all();
    }
    public function generarListaAdicionales(){
        $grupoCodigo = $this->resumenTramo->grupo_codigo;
        $tramoId = $this->resumenTramo->tramo_id;
        
        return CuadResumenPorTramo::where('tramo_id',$tramoId)
        ->where('grupo_codigo',$grupoCodigo)
        ->where('tipo','adicional')
        ->where('condicion','PAGADO')
        ->get()
        ->map(function($item){
            return [
                'descripcion'=>$item->descripcion,
                'fecha'=>$item->fecha,
                'recibo'=>$item->recibo,
                'deuda' => $item->deuda_acumulada
            ];
        })->toArray();
    }
}