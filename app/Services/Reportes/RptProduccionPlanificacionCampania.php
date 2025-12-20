<?php

namespace App\Services\Reportes;

use App\Support\ExcelHelper;
use Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class RptProduccionPlanificacionCampania
{
    public function descargarReporteGeneral($registros, $campo, $campania)
    {

        // 1️⃣ Cargar plantilla
        $spreadsheet = ExcelHelper::cargarPlantilla('rpt_tmpl_reporte_general_campania.xlsx');

        $hoja = $spreadsheet->getSheetByName('RESUMEN_CAMPANIA');

        if (!$hoja) {
            throw new Exception("La plantilla no contiene la hoja 'RESUMEN_CAMPANIA'.");
        }

        // 3️⃣ Cabecera
        $hoja->setCellValue("C2", $campo);
        $hoja->setCellValue("C3", $campania);

        // 4️⃣ Cargar registros
        $fila = 7;
        $filaInicioDatos = $fila;

        foreach ($registros as $reg) {

            $hoja->setCellValue("A{$fila}", $reg->nombre_campania);
            $hoja->setCellValue("B{$fila}", $reg->campo);
            $hoja->setCellValue("C{$fila}", $reg->area);
            $hoja->setCellValue("D{$fila}", $reg->fecha_inicio);

            // Población plantas
            $hoja->setCellValue("E{$fila}", $reg->pp_dia_cero_fecha_evaluacion);
            $hoja->setCellValue("F{$fila}", $reg->pp_dia_cero_numero_pencas_madre);
            $hoja->setCellValue("G{$fila}", $reg->pp_resiembra_fecha_evaluacion);
            $hoja->setCellValue("H{$fila}", $reg->pp_resiembra_numero_pencas_madre);

            // Brotes por piso (NUEVAS COLUMNAS)
            $hoja->setCellValue("I{$fila}", $reg->brotexpiso_fecha_evaluacion);
            $hoja->setCellValue("J{$fila}", $reg->brotexpiso_actual_brotes_2piso);
            $hoja->setCellValue("K{$fila}", $reg->brotexpiso_brotes_2piso_n_dias);
            $hoja->setCellValue("L{$fila}", $reg->brotexpiso_actual_brotes_3piso);
            $hoja->setCellValue("M{$fila}", $reg->brotexpiso_brotes_3piso_n_dias);
            $hoja->setCellValue("N{$fila}", $reg->brotexpiso_actual_total_brotes_2y3piso);
            $hoja->setCellValue("O{$fila}", $reg->brotexpiso_total_brotes_2y3piso_n_dias);

            // Infestación
            $hoja->setCellValue("P{$fila}", $reg->infestacion_fecha);
            $hoja->setCellValue("Q{$fila}", $reg->tipo_infestador);
            $hoja->setCellValue("R{$fila}", $reg->numero_infestadores);
            $hoja->setCellValue("S{$fila}", $reg->infestacion_kg_totales_madre);
            $hoja->setCellValue("T{$fila}", $reg->infestacion_numero_pencas);
            $hoja->setCellValue("U{$fila}", $reg->numero_infestadores_por_penca);
            $hoja->setCellValue("V{$fila}", $reg->gramos_cochinilla_mama_por_infestador);
            $hoja->setCellValue("W{$fila}", $reg->infestacion_duracion_desde_campania);

            // Nutrientes desde inicio infestación
            $hoja->setCellValue("X{$fila}", $reg->nitrogeno_desde_inicio_infestacion);
            $hoja->setCellValue("Y{$fila}", $reg->fosforo_desde_inicio_infestacion);
            $hoja->setCellValue("Z{$fila}", $reg->potasio_desde_inicio_infestacion);
            $hoja->setCellValue("AA{$fila}", $reg->calcio_desde_inicio_infestacion);
            $hoja->setCellValue("AB{$fila}", $reg->magnesio_desde_inicio_infestacion);
            $hoja->setCellValue("AC{$fila}", $reg->manganeso_desde_inicio_infestacion);
            $hoja->setCellValue("AD{$fila}", $reg->zinc_desde_inicio_infestacion);
            $hoja->setCellValue("AE{$fila}", $reg->fierro_desde_inicio_infestacion);
            $hoja->setCellValue("AF{$fila}", $reg->corrector_salinidad_desde_inicio_infestacion);

            // Riego
            $hoja->setCellValue("AG{$fila}", $reg->riego_m3_ini_infest);
            $hoja->setCellValue("AH{$fila}", $reg->riego_m3_ini_infest_por_penca);

            // Reinfestación (CONTINÚA DESDE AH)
            $hoja->setCellValue("AI{$fila}", $reg->reinfestacion_fecha);
            $hoja->setCellValue("AJ{$fila}", $reg->tipo_reinfestador);
            $hoja->setCellValue("AK{$fila}", $reg->numero_reinfestadores);
            $hoja->setCellValue("AL{$fila}", $reg->reinfestacion_kg_totales_madre);
            $hoja->setCellValue("AM{$fila}", $reg->reinfestacion_numero_pencas);
            $hoja->setCellValue("AN{$fila}", $reg->numero_reinfestadores_por_penca);
            $hoja->setCellValue("AO{$fila}", $reg->gramos_cochinilla_mama_por_reinfestador);
            $hoja->setCellValue("AP{$fila}", $reg->reinfestacion_duracion_desde_infestacion);

            // Nutrientes desde infestación a reinfestación
            $hoja->setCellValue("AQ{$fila}", $reg->nitrogeno_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AR{$fila}", $reg->fosforo_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AS{$fila}", $reg->potasio_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AT{$fila}", $reg->calcio_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AU{$fila}", $reg->magnesio_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AV{$fila}", $reg->manganeso_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AW{$fila}", $reg->zinc_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AX{$fila}", $reg->fierro_desde_infestacion_reinfestacion);
            $hoja->setCellValue("AY{$fila}", $reg->corrector_salinidad_desde_infestacion_reinfestacion);

            // Riego
            $hoja->setCellValue("AZ{$fila}", $reg->riego_m3_infest_reinf);
            $hoja->setCellValue("BA{$fila}", $reg->riego_m3_infest_reinfest_por_penca);


            // Cosecha
            $hoja->setCellValue("BB{$fila}", $reg->cosch_fecha);
            $hoja->setCellValue("BC{$fila}", $reg->cosch_tiempo_inf_cosch);
            $hoja->setCellValue("BD{$fila}", $reg->cosch_tiempo_reinf_cosch);
            $hoja->setCellValue("BE{$fila}", $reg->cosch_tiempo_ini_cosch);

            // Nutrientes totales hasta cosecha
            $hoja->setCellValue("BF{$fila}", $reg->nutriente_nitrogeno_kg);
            $hoja->setCellValue("BG{$fila}", $reg->nutriente_fosforo_kg);
            $hoja->setCellValue("BH{$fila}", $reg->nutriente_potasio_kg);
            $hoja->setCellValue("BI{$fila}", $reg->nutriente_calcio_kg);
            $hoja->setCellValue("BJ{$fila}", $reg->nutriente_magnesio_kg);
            $hoja->setCellValue("BK{$fila}", $reg->nutriente_manganeso_kg);
            $hoja->setCellValue("BL{$fila}", $reg->nutriente_zinc_kg);
            $hoja->setCellValue("BM{$fila}", $reg->nutriente_fierro_kg);
            $hoja->setCellValue("BN{$fila}", $reg->corrector_salinidad_cant);

            // Riego
            $hoja->setCellValue("BO{$fila}", $reg->riego_hrs_acumuladas);
            $hoja->setCellValue("BP{$fila}", $reg->riego_m3_inicio_a_reinfestacion_por_penca);

            // Producción y rendimiento
            $hoja->setCellValue("BQ{$fila}", $reg->cosch_total_cosecha);
            $hoja->setCellValue("BR{$fila}", $reg->cosch_produccion_total_kg_seco);
            $hoja->setCellValue("BS{$fila}", $reg->cosch_rendimiento_por_infestador);
            $hoja->setCellValue("BT{$fila}", $reg->cosch_rendimiento_x_penca);

            // Evaluaciones y proyecciones
            $hoja->setCellValue("BU{$fila}", $reg->eval_cosch_proj_rdto_ha);
            $hoja->setCellValue("BV{$fila}", $reg->proj_rdto_prom_rdto_ha);
            $hoja->setCellValue("BW{$fila}", $reg->proj_diferencia_conteo);
            $hoja->setCellValue("BX{$fila}", $reg->proj_diferencia_poda);

            // Análisis financiero
            $hoja->setCellValue("BY{$fila}", $reg->analisis_financiero_costo);
            $hoja->setCellValue("BZ{$fila}", $reg->analisis_financiero_precio_venta);
            $hoja->setCellValue("CA{$fila}", $reg->analisis_financiero_venta_total);
            $hoja->setCellValue("CB{$fila}", $reg->analisis_financiero_utilidad);
            $hoja->setCellValue("CC{$fila}", $reg->analisis_financiero_costo_x_kilo);
            $hoja->setCellValue("CD{$fila}", $reg->analisis_financiero_porcentaje_utilidad);


            $fila++;
        }

        // Guardamos la última fila con datos
        $filaFinDatos = $fila - 1;

        // 5️⃣ Agregar bordes a todo el rango A7:J(última fila)
        $rango = "A{$filaInicioDatos}:CD{$filaFinDatos}";

        $hoja->getStyle($rango)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        return ExcelHelper::descargar($spreadsheet, 'resumen_campañas.xlsx');
    }
}