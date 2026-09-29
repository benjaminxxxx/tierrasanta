<?php

namespace Database\Seeders;

use App\Models\PlanMensualPersonal;
use App\Services\Costos\Consolidacion\BddManoObraServicio;
use App\Services\Costos\Consolidacion\ConsolidarManoObraIndirectaServicio;
use App\Services\Riego\Simulacion\GeneradorRiegoDetalladoSimuladoService;
use App\Services\Riego\Simulacion\SeleccionadorRegadoresSimuladosServicio;
use App\Services\Sistema\Simulacion\GeneradorDatosSimuladosService;
use App\Services\Sistema\Simulacion\GeneradorPlanillaMensualSimuladaService;
use App\Models\ResumenCostoDiario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Simulación para validar el cuadre de costos de planilla de un mes:
 *
 *   costo en campo + mano de obra indirecta (+ banco de horas de riego) = total pagado
 *
 * Además de asistencias y faltas, genera los casos que pagan sin trabajo en campo:
 * feriado (con y sin derecho), descanso médico (4 u 8 h), atención médica, vacaciones
 * (días V + monto pagado) y bono de asistencia.
 *
 * ⚠ Crea trabajadores y registros de prueba: usar solo en una base de pruebas.
 */
class TestCostosCalculoSeeder extends Seeder
{
    private const ANIO = 2026;
    private const MES = 8;

    public function run(
        GeneradorDatosSimuladosService $generador,
        GeneradorPlanillaMensualSimuladaService $generadorPlanilla,
    ): void {
        $this->command->info('Generando trabajadores y asistencias (con feriado, DM, AM y vacaciones)...');
        $generador->crearTrabajadoresContratadosEnPlanilla(50);
        $generador->asignarLaboresAleatoriasEnMes(self::ANIO, self::MES, false, [
            'feriados' => ['2026-08-06'],   // Batalla de Junín
            'prob_dm' => 0.03,
            'prob_am' => 0.01,
            'vacaciones' => 3,
        ]);

        $this->command->info('Generando riego diario de regadores fijos...');
        $seleccionador = app(SeleccionadorRegadoresSimuladosServicio::class);
        app(GeneradorRiegoDetalladoSimuladoService::class)
            ->generarParaMes($seleccionador->seleccionar(4), $seleccionador->camposDisponibles(), self::ANIO, self::MES);

        $this->command->info('Generando planilla mensual...');
        $generadorPlanilla->generarPlanillaMensual(self::ANIO, self::MES);

        $this->command->info('Registrando vacaciones pagadas y bono de asistencia...');
        $this->registrarPagosSinCampo();

        $this->command->info('Actualizando la BDD de costos (mano de obra + indirecta)...');
        app(BddManoObraServicio::class)->asegurarMes(self::ANIO, self::MES, true);

        $this->validarCuadre();
    }

    /** Vacaciones pagadas (días V × jornal) y bono de 100 a quien no faltó en el mes. */
    private function registrarPagosSinCampo(): void
    {
        $dias = DB::table('plan_registros_diarios as r')
            ->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id')
            ->join('plan_mensuales as m', 'm.id', '=', 'd.plan_mensual_id')
            ->where('m.mes', self::MES)->where('m.anio', self::ANIO)
            ->selectRaw("d.plan_empleado_id, SUM(r.asistencia = 'V') as v, SUM(r.asistencia = 'F') as f")
            ->groupBy('d.plan_empleado_id')
            ->get()
            ->keyBy('plan_empleado_id');

        PlanMensualPersonal::whereHas('planMensual', fn($q) => $q->where('mes', self::MES)->where('anio', self::ANIO))
            ->get()
            ->each(function (PlanMensualPersonal $p) use ($dias) {
                $d = $dias->get($p->plan_empleado_id);
                $p->update([
                    'vacaciones_neto_pagadas' => $d && $d->v > 0 ? round($d->v * (float) $p->pago_jornal_diario, 2) : null,
                    'bonificacion_asistencia' => $d && (int) $d->f === 0 ? 100 : null,
                ]);
            });
    }

    private function validarCuadre(): void
    {
        $moi = app(ConsolidarManoObraIndirectaServicio::class);
        $inicio = sprintf('%04d-%02d-01', self::ANIO, self::MES);
        $fin = date('Y-m-t', strtotime($inicio));

        $pagado = $moi->totalPagado(self::ANIO, self::MES);
        $campo = (float) ResumenCostoDiario::where('origen_tipo', 'planilla')->whereBetween('fecha', [$inicio, $fin])->sum('costo_total');
        $conceptos = $moi->totalesPorConcepto($inicio, $fin);
        $indirecta = array_sum(array_column($conceptos, 'costo'));
        $bancoRiego = array_sum(array_column($moi->diferenciasPorTrabajador(self::ANIO, self::MES), 'diferencia'));
        $diferencia = $pagado['total'] - ($campo + $indirecta);

        $this->command->table(['Concepto', 'Monto'], array_merge(
            [
                ['Pagado: sueldo + aportes', number_format($pagado['sueldo_aportes'], 2)],
                ['Pagado: vacaciones', number_format($pagado['vacaciones_neto_pagadas'], 2)],
                ['Pagado: bono asistencia', number_format($pagado['bonificacion_asistencia'], 2)],
                ['TOTAL PAGADO', number_format($pagado['total'], 2)],
                ['Costo en campo', number_format($campo, 2)],
            ],
            array_map(fn($c, $t) => ["  Indirecta: {$c}", number_format($t['costo'], 2)], array_keys($conceptos), $conceptos),
            [
                ['TOTAL COSTO', number_format($campo + $indirecta, 2)],
                ['Diferencia (pagado − costo)', number_format($diferencia, 2)],
                ['Banco de horas de riego (explica)', number_format(-$bancoRiego, 2)],
            ]
        ));

        if (abs($diferencia + $bancoRiego) < 1) {
            $this->command->info('✔ Cuadra: la diferencia es solo el banco de horas de riego.');
        } else {
            $this->command->error('✘ No cuadra: queda ' . number_format($diferencia + $bancoRiego, 2) . ' sin explicar.');
        }
    }
}
