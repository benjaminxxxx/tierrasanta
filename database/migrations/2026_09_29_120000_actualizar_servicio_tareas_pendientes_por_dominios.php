<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reestructuración por dominios (29/09/2026): tareas_pendientes.servicio guarda el nombre completo
 * de la clase detectora; se actualiza a su nuevo namespace.
 */
return new class extends Migration {
    private const MAPA = [
        'App\\Services\\ActividadMetodoServicio' => 'App\\Services\\Planilla\\RegistroDiario\\ActividadMetodoServicio',
        'App\\Services\\ActividadesResumenServicio' => 'App\\Services\\Planilla\\RegistroDiario\\ActividadesResumenServicio',
        'App\\Services\\AlmacenService' => 'App\\Services\\Almacen\\AlmacenPrincipalServicio',
        'App\\Services\\AlmacenServicio' => 'App\\Services\\Almacen\\AlmacenServicio',
        'App\\Services\\Almacen\\InsumoKardexImportarServicio' => 'App\\Services\\Almacen\\Kardex\\InsumoKardexImportarServicio',
        'App\\Services\\Almacen\\InsumoKardexMovimientosServicio' => 'App\\Services\\Almacen\\Kardex\\InsumoKardexMovimientosServicio',
        'App\\Services\\Almacen\\InsumoKardexServicio' => 'App\\Services\\Almacen\\Kardex\\InsumoKardexServicio',
        'App\\Services\\Almacen\\KardexActualizacionServicio' => 'App\\Services\\Almacen\\Kardex\\KardexActualizacionServicio',
        'App\\Services\\Almacen\\KardexReporteServicio' => 'App\\Services\\Almacen\\Kardex\\KardexReporteServicio',
        'App\\Services\\AsistenciasResumenServicio' => 'App\\Services\\Planilla\\Asistencia\\AsistenciasResumenServicio',
        'App\\Services\\AuditoriaServicio' => 'App\\Services\\Reporte\\AuditoriaServicio',
        'App\\Services\\Bonificacion\\GuardarBonificacionProceso' => 'App\\Services\\Cuadrilla\\GuardarBonificacionProceso',
        'App\\Services\\CampaniaServicio' => 'App\\Services\\Campania\\CampaniaHistorialServicio',
        'App\\Services\\Campania\\Data\\DataCostoServicio' => 'App\\Services\\Costos\\Data\\DataCostoServicio',
        'App\\Services\\Campania\\Data\\DataInsumoServicio' => 'App\\Services\\Costos\\Data\\DataInsumoServicio',
        'App\\Services\\Campania\\Data\\DataReporteCampoServicio' => 'App\\Services\\Costos\\Data\\DataReporteCampoServicio',
        'App\\Services\\Campania\\Exports\\ExportCampaniaServicio' => 'App\\Services\\Campania\\ExportCampaniaServicio',
        'App\\Services\\Campo\\Costos\\ConsolidarCostoGastosGeneralesServicio' => 'App\\Services\\Costos\\Consolidacion\\ConsolidarCostoGastosGeneralesServicio',
        'App\\Services\\Campo\\Costos\\ConsolidarCostoInsumosServicio' => 'App\\Services\\Costos\\Consolidacion\\ConsolidarCostoInsumosServicio',
        'App\\Services\\Campo\\Costos\\ConsolidarCostoManoObraServicio' => 'App\\Services\\Costos\\Consolidacion\\ConsolidarCostoManoObraServicio',
        'App\\Services\\Campo\\Costos\\ConsolidarCostoMaquinariaServicio' => 'App\\Services\\Costos\\Consolidacion\\ConsolidarCostoMaquinariaServicio',
        'App\\Services\\Campo\\Costos\\ConsolidarCostoPlanillaServicio' => 'App\\Services\\Costos\\Consolidacion\\ConsolidarCostoPlanillaServicio',
        'App\\Services\\Campo\\Costos\\ConsolidarCostoServiciosCampoServicio' => 'App\\Services\\Costos\\Consolidacion\\ConsolidarCostoServiciosCampoServicio',
        'App\\Services\\Campo\\Costos\\ConsolidarReporteMensualCostos' => 'App\\Services\\Costos\\Consolidacion\\ConsolidarReporteMensualCostos',
        'App\\Services\\Campo\\Gestion\\CampoServicio' => 'App\\Services\\Campo\\CampoServicio',
        'App\\Services\\Campo\\Riego\\RiegoServicio' => 'App\\Services\\Riego\\RiegoServicio',
        'App\\Services\\Campo\\Servicios\\ServicioCampoServicio' => 'App\\Services\\Campo\\ServicioCampoServicio',
        'App\\Services\\Categoria\\SubcategoriaServicio' => 'App\\Services\\Producto\\SubcategoriaServicio',
        'App\\Services\\CochinillaIngresoServicio' => 'App\\Services\\Cochinilla\\CochinillaIngresoServicio',
        'App\\Services\\Configuracion\\ConfiguracionHistorialProceso' => 'App\\Services\\Sistema\\ConfiguracionHistorialProceso',
        'App\\Services\\Configuracion\\ConfiguracionHistorialServicio' => 'App\\Services\\Sistema\\ConfiguracionHistorialServicio',
        'App\\Services\\Contabilidad\\CostosMensualesServicio' => 'App\\Services\\Costos\\CostosMensualesServicio',
        'App\\Services\\Contabilidad\\DistribucionCostoMensualServicio' => 'App\\Services\\Costos\\DistribucionCostoMensualServicio',
        'App\\Services\\CostoFdmServicio' => 'App\\Services\\Costos\\Fdm\\CostoFdmServicio',
        'App\\Services\\CrudCampaniaServicio' => 'App\\Services\\Campania\\CrudCampaniaServicio',
        'App\\Services\\CuadrillaServicio' => 'App\\Services\\Cuadrilla\\CuadrillaServicio',
        'App\\Services\\Cuadrillas\\TramoCuadrilleroServicio' => 'App\\Services\\Cuadrilla\\TramoCuadrilleroServicio',
        'App\\Services\\Excel\\Planilla\\ExcelPlanillaMensual' => 'App\\Services\\Planilla\\Excel\\ExcelPlanillaMensual',
        'App\\Services\\FDM\\CostoServicio' => 'App\\Services\\Costos\\Fdm\\CostoServicio',
        'App\\Services\\FDM\\CuadrillaFdmServicio' => 'App\\Services\\Costos\\Fdm\\CuadrillaFdmServicio',
        'App\\Services\\FDM\\MaquinariaFdmServicio' => 'App\\Services\\Costos\\Fdm\\MaquinariaFdmServicio',
        'App\\Services\\FDM\\PlanillaFdmServicio' => 'App\\Services\\Costos\\Fdm\\PlanillaFdmServicio',
        'App\\Services\\GeneradorDatosSimuladosService' => 'App\\Services\\Sistema\\Simulacion\\GeneradorDatosSimuladosService',
        'App\\Services\\GeneradorPlanillaMensualSimuladaService' => 'App\\Services\\Sistema\\Simulacion\\GeneradorPlanillaMensualSimuladaService',
        'App\\Services\\GeneradorSimulacionService' => 'App\\Services\\Sistema\\Simulacion\\GeneradorSimulacionService',
        'App\\Services\\GestionCuadrilla\\DescripcionDetallePagoServicio' => 'App\\Services\\Cuadrilla\\Pago\\DescripcionDetallePagoServicio',
        'App\\Services\\GestionCuadrilla\\DesgloseServicio' => 'App\\Services\\Cuadrilla\\Pago\\DesgloseServicio',
        'App\\Services\\GestionCuadrilla\\PagoBonoCuadrillaServicio' => 'App\\Services\\Cuadrilla\\Pago\\PagoBonoCuadrillaServicio',
        'App\\Services\\GestionCuadrilla\\PagoCuadrillaServicio' => 'App\\Services\\Cuadrilla\\Pago\\PagoCuadrillaServicio',
        'App\\Services\\GestionCuadrilla\\PagoGastoAdicionalServicio' => 'App\\Services\\Cuadrilla\\Pago\\PagoGastoAdicionalServicio',
        'App\\Services\\GestionCuadrilla\\RangoPeriodoServicio' => 'App\\Services\\Cuadrilla\\Pago\\RangoPeriodoServicio',
        'App\\Services\\Handsontable\\HSTCuadrillaRegistroDiarioActividades' => 'App\\Services\\Cuadrilla\\Handsontable\\HSTCuadrillaRegistroDiarioActividades',
        'App\\Services\\Handsontable\\HSTCuadrillaReporteSemanalHoras' => 'App\\Services\\Cuadrilla\\Handsontable\\HSTCuadrillaReporteSemanalHoras',
        'App\\Services\\Handsontable\\HSTPlanillaAsistencia' => 'App\\Services\\Planilla\\Handsontable\\HSTPlanillaAsistencia',
        'App\\Services\\Handsontable\\HSTPlanillaRegistroDiarioActividades' => 'App\\Services\\Planilla\\Handsontable\\HSTPlanillaRegistroDiarioActividades',
        'App\\Services\\InformacionGeneral\\LaboresServicio' => 'App\\Services\\Campo\\Labor\\LaboresServicio',
        'App\\Services\\InformacionGeneral\\MaquinariaServicio' => 'App\\Services\\Campo\\MaquinariaServicio',
        'App\\Services\\InformacionGeneral\\ProveedorServicio' => 'App\\Services\\Almacen\\ProveedorServicio',
        'App\\Services\\Insumo\\InsumoServicio' => 'App\\Services\\Producto\\InsumoServicio',
        'App\\Services\\Insumo\\InsumoUsoServicio' => 'App\\Services\\Producto\\InsumoUsoServicio',
        'App\\Services\\Insumos\\VerificacionKardexServicio' => 'App\\Services\\Almacen\\Kardex\\VerificacionKardexServicio',
        'App\\Services\\Labor\\ImportarLaborProceso' => 'App\\Services\\Campo\\Labor\\ImportarLaborProceso',
        'App\\Services\\Labor\\LaborServicio' => 'App\\Services\\Campo\\Labor\\LaborServicio',
        'App\\Services\\Modulos\\Planilla\\GestionPlanilla' => 'App\\Services\\Planilla\\Modulos\\GestionPlanilla',
        'App\\Services\\Modulos\\Planilla\\GestionPlanillaEmpleados' => 'App\\Services\\Planilla\\Modulos\\GestionPlanillaEmpleados',
        'App\\Services\\Modulos\\Planilla\\GestionPlanillaReporteDiario' => 'App\\Services\\Planilla\\Modulos\\GestionPlanillaReporteDiario',
        'App\\Services\\Modulos\\Planilla\\GestionPlanillaResumenGeneral' => 'App\\Services\\Planilla\\Modulos\\GestionPlanillaResumenGeneral',
        'App\\Services\\Modulos\\ReporteDiarioCuadrillaServicio' => 'App\\Services\\Cuadrilla\\ReporteDiarioCuadrillaServicio',
        'App\\Services\\PlanSueldoServicio' => 'App\\Services\\Planilla\\PlanSueldoServicio',
        'App\\Services\\PlanTipoAsistenciaServicio' => 'App\\Services\\Planilla\\Asistencia\\PlanTipoAsistenciaServicio',
        'App\\Services\\PlanillaMensualServicio' => 'App\\Services\\Planilla\\PlanillaMensualServicio',
        'App\\Services\\PlanillaServicio' => 'App\\Services\\Planilla\\PlanillaSueldoGastoServicio',
        'App\\Services\\Produccion\\MateriaPrima\\BrotesPorPisoServicio' => 'App\\Services\\Evaluacion\\BrotesPorPisoServicio',
        'App\\Services\\Produccion\\MateriaPrima\\PoblacionPlantaServicio' => 'App\\Services\\Evaluacion\\PoblacionPlantaServicio',
        'App\\Services\\Produccion\\Planificacion\\CampaniaServicio' => 'App\\Services\\Campania\\CampaniaServicio',
        'App\\Services\\ProductoServicio' => 'App\\Services\\Producto\\ProductoServicio',
        'App\\Services\\RecursosHumanos\\Asistencias\\Planilla\\ResumenAsistenciaPlanillaServicio' => 'App\\Services\\Planilla\\Asistencia\\ResumenAsistenciaPlanillaServicio',
        'App\\Services\\RecursosHumanos\\Data\\DataEmpleadoServicio' => 'App\\Services\\Planilla\\Empleado\\DataEmpleadoServicio',
        'App\\Services\\RecursosHumanos\\Personal\\ActividadServicio' => 'App\\Services\\Planilla\\Empleado\\ActividadServicio',
        'App\\Services\\RecursosHumanos\\Personal\\ContratoServicio' => 'App\\Services\\Planilla\\Empleado\\ContratoServicio',
        'App\\Services\\RecursosHumanos\\Personal\\EmpleadoServicio' => 'App\\Services\\Planilla\\Empleado\\EmpleadoServicio',
        'App\\Services\\RecursosHumanos\\Personal\\ImportContratoServicio' => 'App\\Services\\Planilla\\Empleado\\ImportContratoServicio',
        'App\\Services\\RecursosHumanos\\Personal\\ValidarContratoServicio' => 'App\\Services\\Planilla\\Empleado\\ValidarContratoServicio',
        'App\\Services\\RecursosHumanos\\Planilla\\EmpleadoPerfilServicio' => 'App\\Services\\Planilla\\Empleado\\EmpleadoPerfilServicio',
        'App\\Services\\RecursosHumanos\\Planilla\\PlanillaAsistenciaServicio' => 'App\\Services\\Planilla\\Asistencia\\PlanillaAsistenciaServicio',
        'App\\Services\\RecursosHumanos\\Planilla\\PlanillaEmpleadoServicio' => 'App\\Services\\Planilla\\PlanillaEmpleadoServicio',
        'App\\Services\\RecursosHumanos\\Planilla\\PlanillaMensualDetalleServicio' => 'App\\Services\\Planilla\\PlanillaMensualDetalleServicio',
        'App\\Services\\RecursosHumanos\\Planilla\\PlanillaRegistroDiarioProcesoSuspensionesPendientes' => 'App\\Services\\Planilla\\RegistroDiario\\PlanillaRegistroDiarioProcesoSuspensionesPendientes',
        'App\\Services\\RecursosHumanos\\Planilla\\PlanillaRegistroDiarioServicio' => 'App\\Services\\Planilla\\RegistroDiario\\PlanillaRegistroDiarioServicio',
        'App\\Services\\RecursosHumanos\\Planilla\\PlanillaServicio' => 'App\\Services\\Planilla\\PlanillaActividadServicio',
        'App\\Services\\Reportes\\RptPlanillaGeneral' => 'App\\Services\\Reporte\\RptPlanillaGeneral',
        'App\\Services\\Reportes\\RptProduccionPlanificacionCampania' => 'App\\Services\\Reporte\\RptProduccionPlanificacionCampania',
        'App\\Services\\Reportes\\RptRecursosHumanosAsistenciasGeneral' => 'App\\Services\\Reporte\\RptRecursosHumanosAsistenciasGeneral',
        'App\\Services\\RiegoServicio' => 'App\\Services\\Riego\\RiegoCampaniaServicio',
        'App\\Services\\SiembraServicio' => 'App\\Services\\Campo\\SiembraServicio',
        'App\\Services\\Simulacion\\GeneradorRiegoDetalladoSimuladoService' => 'App\\Services\\Riego\\Simulacion\\GeneradorRiegoDetalladoSimuladoService',
        'App\\Services\\Simulacion\\GeneradorRiegoDiarioSimuladoService' => 'App\\Services\\Riego\\Simulacion\\GeneradorRiegoDiarioSimuladoService',
        'App\\Services\\Simulacion\\SeleccionadorRegadoresSimuladosServicio' => 'App\\Services\\Riego\\Simulacion\\SeleccionadorRegadoresSimuladosServicio',
        'App\\Services\\TareasPendientes\\TareaPendienteServicio' => 'App\\Services\\Sistema\\TareasPendientes\\TareaPendienteServicio',
    ];

    public function up(): void
    {
        foreach (self::MAPA as $viejo => $nuevo) {
            DB::table("tareas_pendientes")->where("servicio", $viejo)->update(["servicio" => $nuevo]);
        }
    }

    public function down(): void
    {
        foreach (self::MAPA as $viejo => $nuevo) {
            DB::table("tareas_pendientes")->where("servicio", $nuevo)->update(["servicio" => $viejo]);
        }
    }
};