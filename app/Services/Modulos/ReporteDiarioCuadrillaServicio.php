<?php

namespace App\Services\Modulos;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
use App\Services\Handsontable\HSTCuadrillaRegistroDiarioActividades;
use App\Services\RecursosHumanos\Personal\ActividadServicio;

class ReporteDiarioCuadrillaServicio
{
    //METODOS UTILIZADOS EN cuadrilla/gestion_cuadrilleros/registro-diario
    /**
     * Detecta los tramos laborales que aplican para una fecha dada.
     *
     * @param string $fecha La fecha en formato 'Y-m-d'.
     * @return array Un arreglo de tramos laborales que aplican para la fecha.
     */
    public function obtenerTramosEnFecha($fecha){
        return TramoLaboralServicio::tramosEnFecha($fecha);
    }
    /**
     * Obtiene los datos necesarios para renderizar la tabla Handsontable.
     *
     * @param string $fecha La fecha del reporte en formato 'Y-m-d'.
     * @return array Un arreglo con 'data' para la tabla y 'total_columnas'.
     */
    public function obtenerDatosParaReporteDiario(string $fecha,int $tramoSeleccionadoId): array
    {
        // Delega la obtención de datos a un servicio más específico.
        $generador = new HSTCuadrillaRegistroDiarioActividades();
        return $generador->generar($fecha,$tramoSeleccionadoId);
        //return CuadrilleroServicio::obtenerHandsontableReporteDiario($fecha,$tramoSeleccionadoId);
    }
    /**
     * Guarda los datos provenientes de la tabla Handsontable y ejecuta procesos posteriores
     * como la detección y creación de actividades.
     *
     * @param string $fecha La fecha del reporte en formato 'Y-m-d'.
     * @param array $datos Los datos de la tabla.
     * @return void
     * @throws \Dotenv\Exception\ValidationException Si la validación de datos falla.
     * @throws \Throwable Para cualquier otro tipo de error.
     */
    public function guardarReporteDiario(string $fecha, array $datos): void
    {
        // 1. Guardar los datos principales del reporte de cuadrilla
        CuadrilleroServicio::guardarDesdeHandsontable($fecha, $datos);
        
        // 2. Ejecutar la lógica de negocio secundaria (detección y creación de actividades)
        ActividadServicio::detectarYCrearActividades($fecha);
    }
}