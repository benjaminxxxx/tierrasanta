<?php

namespace App\Services\Simulacion;

use App\Models\ConsolidadoRiego;
use App\Models\PlanEmpleado;
use App\Services\Campo\Riego\RiegoServicio;
use App\Services\Riego\ConsolidarJornadaRiegoProceso;
use Illuminate\Support\Carbon;

class GeneradorRiegoDetalladoSimuladoService
{
    private const HORA_INICIO_JORNADA = 7 * 60;  // 07:00 en minutos
    private const HORA_FIN_JORNADA = 16 * 60;    // 16:00 en minutos
    private const HORA_INICIO_ALMUERZO = '11:00';
    private const HORA_FIN_ALMUERZO = '12:00';

    protected RiegoServicio $riegoServicio;

    public function __construct(RiegoServicio $riegoServicio)
    {
        $this->riegoServicio = $riegoServicio;
    }

    /**
     * @param array $regadores Formato: [['id'=>.., 'tipo'=>'empleados', 'nombre'=>..], ...]
     * @param array $camposDisponibles Nombres de campos con campaña vigente
     * @return array Conflictos acumulados de todo el mes, para revisión
     */
    public function generarParaMes(array $regadores, array $camposDisponibles, int $anio, int $mes): array
    {
        ConsolidadoRiego::query()->delete();
        $diasEnMes = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;
        $conflictosDelMes = [];

        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fechaCarbon = Carbon::createFromDate($anio, $mes, $dia);

            if ($fechaCarbon->isSunday()) {
                continue;
            }

            $fecha = $fechaCarbon->format('Y-m-d');
            $conflictosDelDia = $this->generarParaFecha($regadores, $camposDisponibles, $fecha);
            $conflictosDelMes = array_merge($conflictosDelMes, $conflictosDelDia);
        }

        return $conflictosDelMes;
    }

    private function generarParaFecha(array $regadores, array $camposDisponibles, string $fecha): array
    {
        // 1. Crea/actualiza el ConsolidadoRiego "vacío" de cada regador para esta fecha
        RiegoServicio::registrarRegadoresEnFecha($fecha, $regadores);

        $slotsOcupadosPorCampo = []; 
        $conflictos = [];

        foreach ($regadores as $regador) {
            $consolidado = ConsolidadoRiego::where('trabajador_id', $regador['id'])
                ->where('trabajador_type', PlanEmpleado::class)
                ->where('fecha', $fecha)
                ->first();

            if (!$consolidado) {
                continue;
            }

            $data = $this->generarTramosDelDia($camposDisponibles, $slotsOcupadosPorCampo);

            $parametros = [
                'resumen_riego'        => $consolidado,
                'fecha'                => $fecha,
                'data'                 => $data,
                'hora_inicio_almuerzo' => self::HORA_INICIO_ALMUERZO,
                'hora_fin_almuerzo'    => self::HORA_FIN_ALMUERZO,
            ];

            $conflictosRegador = app(ConsolidarJornadaRiegoProceso::class)->ejecutarGuardadoRegistros($parametros);

            if (!empty($conflictosRegador)) {
                $conflictos[] = [
                    'fecha'      => $fecha,
                    'trabajador' => $regador['nombre'],
                    'detalle'    => $conflictosRegador,
                ];
            }
        }

        // 2. Generar y procesar los registros diarios de riego hacia planillas
        $listaPorEnviar = $this->riegoServicio->generarRegistroDiarioParaRegadores($fecha);

        if ($listaPorEnviar->isNotEmpty()) {
            $this->riegoServicio->registrarDiarioRegadores($fecha, $listaPorEnviar);
        }

        return $conflictos;
    }

    /**
     * Arma los tramos de un regador cubriendo 07:00–16:00 físico.
     */
    private function generarTramosDelDia(array $camposDisponibles, array &$slotsOcupadosPorCampo): array
    {
        $cantidadTramos = rand(2, 3);
        $minutosPorTramo = intdiv(self::HORA_FIN_JORNADA - self::HORA_INICIO_JORNADA, $cantidadTramos);

        $tramos = [];
        $cursor = self::HORA_INICIO_JORNADA;

        for ($i = 0; $i < $cantidadTramos; $i++) {
            $finTramo = ($i === $cantidadTramos - 1)
                ? self::HORA_FIN_JORNADA
                : $cursor + $minutosPorTramo;

            $campo = $this->elegirCampoLibre($camposDisponibles, $cursor, $finTramo, $slotsOcupadosPorCampo);

            $slotsOcupadosPorCampo[$campo][] = [$cursor, $finTramo];

            $horas = round(($finTramo - $cursor) / 60, 2);

            $tramos[] = [
                $campo,                         // 0: campo
                $this->minutosAHora($cursor),   // 1: hora_inicio
                $this->minutosAHora($finTramo), // 2: hora_fin
                $horas,                         // 3: horas
                'Riego',                        // 4: labor
                null,                           // 5: observación
                null,                           // 6: sin_haberes
                null,                           // 7: aux
            ];

            $cursor = $finTramo;
        }

        return $tramos;
    }

    private function elegirCampoLibre(array $camposDisponibles, int $inicio, int $fin, array $slotsOcupadosPorCampo): string
    {
        foreach (collect($camposDisponibles)->shuffle() as $campo) {
            $ocupado = false;

            foreach ($slotsOcupadosPorCampo[$campo] ?? [] as [$slotInicio, $slotFin]) {
                if ($inicio < $slotFin && $fin > $slotInicio) {
                    $ocupado = true;
                    break;
                }
            }

            if (!$ocupado) {
                return $campo;
            }
        }

        throw new \Exception("No hay campos libres para el tramo {$this->minutosAHora($inicio)}-{$this->minutosAHora($fin)} — amplía la lista de campos disponibles.");
    }

    private function minutosAHora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}