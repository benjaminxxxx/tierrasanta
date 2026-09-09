<?php

namespace App\Services\Simulacion;

use App\Models\ConsolidadoRiego;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualDetalle;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use App\Services\Campo\Riego\RiegoServicio;
use Illuminate\Support\Carbon;

class GeneradorRiegoDiarioSimuladoService
{
    private const HORA_INICIO = '07:00:00';
    private const HORA_FIN = '16:00:00';
    private const HORA_INICIO_ALMUERZO = '11:00:00';
    private const HORA_FIN_ALMUERZO = '12:00:00';
    private const MINUTOS_JORNAL = 480; // 9h físicas - 1h almuerzo = 8h netas
    private const CAMPO_RIEGO = 'FDM';
    private const LABOR_RIEGO = 81;

    /**
     * Genera riego de lunes a sábado durante todo el mes para los
     * regadores dados. Domingo se omite (no se riega).
     *
     * @param array $regadores Formato: [['id'=>.., 'tipo'=>'empleados', 'nombre'=>..], ...]
     */
    public function generarParaMes(array $regadores, int $anio, int $mes): void
    {
        $diasEnMes = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;

        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fechaCarbon = Carbon::createFromDate($anio, $mes, $dia);

            if ($fechaCarbon->isSunday()) {
                continue;
            }

            $this->generarParaFecha($regadores, $fechaCarbon->format('Y-m-d'), $mes, $anio);
        }
    }

    private function generarParaFecha(array $regadores, string $fecha, int $mes, int $anio): void
    {
        // 1. Crea/actualiza el ConsolidadoRiego "vacío" de cada regador para esta fecha
        RiegoServicio::registrarRegadoresEnFecha($fecha, $regadores);

        $dataPlanilla = [];

        foreach ($regadores as $regador) {
            $consolidado = ConsolidadoRiego::where('trabajador_id', $regador['id'])
                ->where('trabajador_type', PlanEmpleado::class) // ⚠️ ajustar si algún día hay cuadrilleros aquí
                ->where('fecha', $fecha)
                ->first();

            if (!$consolidado) {
                continue; // no debería pasar, RiegoServicio lo acaba de crear
            }

            // 2. Fijamos horario y minutos YA conocidos, sin pasar por el flujo
            // de tramos manuales (tal como dijiste, en simulación ya sabemos el total)
            $consolidado->update([
                'hora_inicio' => self::HORA_INICIO,
                'hora_fin' => self::HORA_FIN,
                'hora_inicio_almuerzo' => self::HORA_INICIO_ALMUERZO,
                'hora_fin_almuerzo' => self::HORA_FIN_ALMUERZO,
                'minutos_regados' => self::MINUTOS_JORNAL,
                'minutos_jornal' => self::MINUTOS_JORNAL,
                'minutos_acumulados' => 0,
                'minutos_utilizados' => 0,
                'estado' => 'consolidado', // ⚠️ confirmar valor real del enum/string
                'sincronizado' => true,
            ]);

            // 3. plan_men_detalle_id del trabajador para este mes
            $planillaMensual = PlanMensualDetalle::where('plan_empleado_id', $regador['id'])
                ->whereHas('planillaMensual', function ($q) use ($mes, $anio) {
                    $q->where('mes', $mes)->where('anio', $anio);
                })
                ->first();

            if (!$planillaMensual) {
                throw new \Exception("No existe registro mensual para el trabajador id {$regador['id']} en {$mes}/{$anio}. ¿Corriste asignarLaboresAleatoriasEnMes() antes?");
            }

            // 4. Igual que enviarRegistroDiarioRegadores(): la hora "reportada"
            // es hora_inicio + minutos_jornal, NO la hora física de salida
            // (07:00 + 480min = 15:00, no 16:00 — el almuerzo ya está descontado)
            $horaInicio = Carbon::parse(self::HORA_INICIO);
            $horaFinReportada = $horaInicio->copy()->addMinutes(self::MINUTOS_JORNAL);

            $dataPlanilla[] = [
                'plan_men_detalle_id' => $planillaMensual->id,
                'asistencia' => 'A',
                'total_horas' => round(self::MINUTOS_JORNAL / 60, 2),
                'campo_1' => self::CAMPO_RIEGO,
                'labor_1' => self::LABOR_RIEGO,
                'entrada_1' => $horaInicio->format('H:i:s'),
                'salida_1' => $horaFinReportada->format('H:i:s'),
            ];
        }

        if (empty($dataPlanilla)) {
            return;
        }

        // 5. Reemplaza el registro diario de estos 4 trabajadores para esta fecha
        // (mismo comportamiento que confirmarEnvio(): pisa cualquier asignación
        // aleatoria que asignarLaboresAleatoriasEnMes() haya puesto ese día)
        app(PlanillaRegistroDiarioServicio::class)->guardarRegistrosDiarios($fecha, $dataPlanilla, 1);
    }
}