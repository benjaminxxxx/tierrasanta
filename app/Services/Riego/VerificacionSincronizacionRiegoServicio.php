<?php

namespace App\Services\Riego;

use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\TareaPendiente;
use App\Services\Riego\Cuadrilla\RiegoCuadrillaConsulta;
use App\Services\Riego\Cuadrilla\RiegoCuadrillaDetector;
use App\Services\Riego\Cuadrilla\RiegoCuadrillaReglas;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use App\Services\Sistema\Vigencia\SistemaVigenciaReglas;
use Illuminate\Support\Carbon;

/**
 * Riego frente al registro diario: el jornal de riego de cada regador debe coincidir con su registro diario
 * (planilla: horas de sus tramos; cuadrilla: FDM/81, ver RiegoCuadrillaConsulta).
 * La tarea pendiente lista cada trabajador y día con el motivo y el enlace a su módulo; al guardar el registro
 * diario de planilla o de cuadrilla se vuelve a verificar ese día solo.
 */
class VerificacionSincronizacionRiegoServicio
{
    public const TIPO = 'riego-desincronizado';

    /** Diferencia (minutos) que se tolera por redondeo */
    private const TOLERANCIA_MIN = 3;

    public function verificarPorFecha(string $fecha): array
    {
        $resultado = ['ok' => 0, 'desincronizados' => 0, 'omitidos' => 0];

        foreach (ConsolidadoRiego::whereDate('fecha', $fecha)->get() as $item) {
            $revision = $this->revisar($item);
            if ($revision === null) {
                $resultado['omitidos']++;
                continue;
            }
            if ((bool) $item->sincronizado !== $revision['sincronizado']) {
                $item->update(['sincronizado' => $revision['sincronizado']]);
            }
            $revision['sincronizado'] ? $resultado['ok']++ : $resultado['desincronizados']++;
        }

        return $resultado;
    }

    /**
     * Revisa un registro de riego. Devuelve null si el trabajador no es de planilla ni de cuadrilla.
     * ['sincronizado' => bool, 'origen' => 'planilla'|'cuadrilla', 'nombre' => string, 'motivo' => ?string]
     */
    public function revisar(ConsolidadoRiego $item): ?array
    {
        $fecha = Carbon::parse($item->fecha);
        $jornal = (int) $item->minutos_jornal;

        if ($item->trabajador_type === Cuadrillero::class) {
            $r = app(RiegoCuadrillaConsulta::class)->revisarConsolidado($item);
            // Sin detalle de riego: como en planilla, se recomienda quitarlo del reporte de regadores
            $tramos = \App\Models\CuadDetalleHora::whereIn('registro_diario_id',
                \App\Models\CuadRegistroDiario::where('cuadrillero_id', $item->trabajador_id)->whereDate('fecha', $fecha)->pluck('id'))->get();
            $esRiego = fn($t) => RiegoCuadrillaReglas::esTramoRiego($t->campo_nombre, $t->codigo_labor);
            $sinRiego = $this->sinDetalleDeRiego($item,
                (int) $tramos->reject($esRiego)->sum(fn($t) => RiegoCuadrillaReglas::minutos($t->hora_inicio, $t->hora_fin)),
                (int) $tramos->filter($esRiego)->sum(fn($t) => RiegoCuadrillaReglas::minutos($t->hora_inicio, $t->hora_fin)));
            if ($sinRiego) {
                return ['sincronizado' => false, 'origen' => 'cuadrilla', 'nombre' => $r['nombre'], 'motivo' => $sinRiego];
            }
            // "es_planilla" y "costo" son informativos: no desincronizan (tienen su propia tarea de riego-cuadrilla)
            $motivos = array_values(array_diff_key($r['problemas'], ['costo' => true, 'es_planilla' => true]));
            return [
                'sincronizado' => (bool) $r['sincronizado'],
                'origen' => 'cuadrilla',
                'nombre' => $r['nombre'],
                'motivo' => $motivos ? implode(' ', $motivos) . ' Corrige el registro diario de cuadrilla y guarda: se vuelve a verificar solo.' : null,
            ];
        }

        if ($item->trabajador_type !== PlanEmpleado::class) {
            return null;
        }

        $nombre = PlanEmpleado::find($item->trabajador_id)?->nombre_completo ?? $item->trabajador_nombre ?? "Empleado {$item->trabajador_id}";
        $base = ['origen' => 'planilla', 'nombre' => $nombre];

        $detalleMensual = PlanMensualDetalle::where('plan_empleado_id', $item->trabajador_id)
            ->whereHas('planillaMensual', fn($q) => $q->where('mes', $fecha->month)->where('anio', $fecha->year))
            ->first();
        if (!$detalleMensual) {
            return $base + ['sincronizado' => false, 'motivo' => 'Regó ' . self::h($jornal) . ' pero no está en la planilla de '
                . $fecha->translatedFormat('F') . ' (revisa su contrato): no tiene registro diario donde cargar el riego.'];
        }

        $registroDiario = PlanRegistroDiario::where('plan_det_men_id', $detalleMensual->id)->whereDate('fecha', $fecha)->first();

        // El riego se compara solo con sus tramos FDM/81: un día mixto (5 h de riego + 3 h en un campo) es válido si
        // FDM/81 tiene las horas del riego. Un día de DM o vacaciones no tiene tramos: con 0 h de riego cuadra.
        $tramos = $registroDiario ? $registroDiario->detalles()->get() : collect();
        $min81 = (int) $tramos->filter(fn($d) => RiegoCuadrillaReglas::esTramoRiego($d->campo_nombre, $d->codigo_labor))
            ->sum(fn($d) => RiegoCuadrillaReglas::minutos($d->hora_inicio, $d->hora_fin));
        $minOtros = (int) $tramos->reject(fn($d) => RiegoCuadrillaReglas::esTramoRiego($d->campo_nombre, $d->codigo_labor))
            ->sum(fn($d) => RiegoCuadrillaReglas::minutos($d->hora_inicio, $d->hora_fin));

        if ($sinRiego = $this->sinDetalleDeRiego($item, $minOtros, $min81)) {
            return $base + ['sincronizado' => false, 'motivo' => $sinRiego];
        }

        if (abs($min81 - $jornal) < self::TOLERANCIA_MIN) {
            return $base + ['sincronizado' => true, 'motivo' => null];
        }

        if (!$registroDiario) {
            $motivo = 'Regó ' . self::h($jornal) . ' y no tiene registro diario de planilla ese día.';
        } else {
            $motivo = 'Jornal de riego ' . self::h($jornal) . '; su registro diario tiene ' . self::h($min81) . ' en FDM/81'
                . ($minOtros ? ' y ' . self::h($minOtros) . ' en otras labores' : '')
                . ($registroDiario->asistencia && $registroDiario->asistencia !== 'A' ? " (asistencia {$registroDiario->asistencia})" : '') . '.';
            if ($min81 === 0 && $minOtros > 0) {
                $motivo .= ' Si el día fue mixto, una de sus líneas debe ser FDM/81 con las horas de riego y las demás, sus otras labores.';
            }
        }

        return $base + ['sincronizado' => false, 'motivo' => $motivo
            . ' Corrige el registro diario (o el reporte de riego, si el error está ahí) y guarda: se vuelve a verificar solo.'];
    }

    /**
     * Está en el reporte de regadores pero no tiene ningún detalle de riego (ni ronda ni horas acumuladas): el sistema
     * entiende que ese día regó 0 h. Si hizo otras labores, no debía agregarse como regador ese día.
     * Una ronda "sin haberes" (jornal 0 con detalle) sí es válida.
     */
    private function sinDetalleDeRiego(ConsolidadoRiego $item, int $minOtros, int $min81): ?string
    {
        if ((int) $item->minutos_jornal > 0 || \App\Models\ReporteDiarioRiego::where('consolidado_id', $item->id)->exists()) {
            return null;
        }
        if ($minOtros > 0 && $min81 === 0) {
            return 'Está en el reporte de regadores, pero no tiene detalle de riego y ese día hizo otras labores ('
                . self::h($minOtros) . ', sin FDM/81). Quítalo del reporte de regadores de ese día: si no, el sistema entiende que regó 0 h.';
        }
        return 'Está en el reporte de regadores sin ningún detalle de riego. Registra su riego o quítalo del reporte de regadores de ese día.';
    }

    /**
     * Verifica un día y deja las tareas pendientes al día. Se llama al guardar el reporte de riego
     * y el registro diario de planilla o de cuadrilla.
     */
    public function verificarFechaYActualizarTareas(string $fecha): array
    {
        $resultado = $this->verificarPorFecha($fecha);
        $this->detectarTareasPendientes();
        app(RiegoCuadrillaDetector::class)->detectarTareasPendientes($fecha);

        return $resultado;
    }

    /**
     * Después de guardar un registro diario (planilla o cuadrilla): si ese día hubo riego, lo vuelve a verificar y
     * devuelve un aviso con quienes siguen sin cuadrar (null si todo cuadra o no hubo riego). Nunca hace fallar el guardado.
     */
    public function verificarDespuesDeGuardar(string $fecha, string $trabajadorType): ?string
    {
        try {
            if (!ConsolidadoRiego::whereDate('fecha', $fecha)->exists()) {
                return null;
            }
            $this->verificarFechaYActualizarTareas($fecha);
            $pendientes = ConsolidadoRiego::whereDate('fecha', $fecha)->where('trabajador_type', $trabajadorType)
                ->where('sincronizado', false)->get()->map(fn($c) => $c->trabajador_nombre)->filter()->values();
            return $pendientes->isEmpty() ? null
                : 'Riego de este día sin cuadrar con el registro diario: ' . $pendientes->implode(', ') . '. Revisa sus horas (detalle en Tareas pendientes).';
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /** Lo mismo para un rango (reporte semanal de cuadrilla). Solo las fechas con riego. */
    public function verificarRangoYActualizarTareas(string $inicio, string $fin): void
    {
        $fechas = ConsolidadoRiego::whereBetween('fecha', [$inicio, $fin])->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->toDateString())->unique()->values();
        if ($fechas->isEmpty()) {
            return;
        }
        $fechas->each(fn($f) => $this->verificarPorFecha($f));
        $this->detectarTareasPendientes();
        $fechas->each(fn($f) => app(RiegoCuadrillaDetector::class)->detectarTareasPendientes($f));
    }

    /**
     * Una subtarea por trabajador y día (con su periodo: el panel la muestra al elegir ese mes), con el motivo y
     * el enlace al registro diario de planilla o de cuadrilla de esa fecha. Desde la vigencia del sistema.
     */
    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        $desde = SistemaVigenciaReglas::desde();
        $desincronizados = ConsolidadoRiego::where('sincronizado', false)
            ->whereIn('trabajador_type', [PlanEmpleado::class, Cuadrillero::class])
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->orderBy('fecha')
            ->get();

        $registrador = app(TareaPendienteServicio::class);
        $casos = [];
        foreach ($desincronizados as $item) {
            $r = $this->revisar($item);
            if (!$r) {
                continue;
            }
            if ($r['sincronizado']) { // la marca estaba vieja
                $item->update(['sincronizado' => true]);
                continue;
            }
            $casos[] = [$item, $r];
        }

        $padre = $registrador->registrarOActualizar([
            'tipo' => self::TIPO,
            'clave' => null,
            'titulo' => 'Registros de riego desincronizados',
            'descripcion' => 'El jornal de riego no coincide con el registro diario del trabajador. Abre su registro diario (planilla o cuadrilla), '
                . 'corrígelo y guarda: la verificación se repite sola.',
            'variante' => 'warning',
            'cantidad_afectados' => count($casos),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [['titulo' => 'Volver a verificar todo', 'metodo' => 'sincronizarTodos', 'parametros' => []]],
        ]);

        $vigentes = [];
        foreach ($casos as [$item, $r]) {
            $fecha = Carbon::parse($item->fecha)->toDateString();
            $clave = $fecha . '-' . class_basename($item->trabajador_type) . '-' . $item->trabajador_id;
            $vigentes[] = $clave;
            $esPlanilla = $r['origen'] === 'planilla';
            $registrador->registrarOActualizar([
                'tipo' => self::TIPO,
                'clave' => $clave,
                'parent_id' => $padre?->id,
                'fecha_inicio' => $fecha,
                'fecha_fin' => $fecha,
                'titulo' => Carbon::parse($fecha)->format('d/m') . ' · ' . $r['nombre'] . ' (' . ($esPlanilla ? 'planilla' : 'cuadrilla') . ')',
                'descripcion' => $r['motivo'],
                'variante' => 'warning',
                'cantidad_afectados' => 1,
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [
                    $esPlanilla
                        ? ['titulo' => 'Registro diario de planilla', 'tipo_accion' => 'link', 'url' => route('planilla.registro_diario', ['fecha' => $fecha])]
                        : ['titulo' => 'Registro diario de cuadrilla', 'tipo_accion' => 'link', 'url' => route('cuadrilla.registro_diario', ['fecha' => $fecha])],
                    ['titulo' => 'Reporte de riego', 'tipo_accion' => 'link', 'url' => route('riego.reporte_diario', ['fecha' => $fecha])],
                    ['titulo' => 'Verificar', 'metodo' => 'verificarFechaYActualizarTareas', 'parametros' => ['fecha' => $fecha]],
                ],
            ]);
        }

        // Lo ya corregido (y las subtareas antiguas por mes) se cierra
        TareaPendiente::where('tipo', self::TIPO)
            ->where('estado', 'pendiente')
            ->whereNotNull('clave')
            ->whereNotIn('clave', $vigentes)
            ->get()
            ->each(fn($vieja) => $registrador->registrarOActualizar(['tipo' => self::TIPO, 'clave' => $vieja->clave, 'cantidad_afectados' => 0]));
    }

    /** Vuelve a verificar todos los días con registros marcados como desincronizados. */
    public function sincronizarTodos(): array
    {
        return $this->sincronizarFechas(ConsolidadoRiego::where('sincronizado', false)->pluck('fecha'));
    }

    public function sincronizarMes(int $mes, int $anio): array
    {
        return $this->sincronizarFechas(
            ConsolidadoRiego::where('sincronizado', false)->whereYear('fecha', $anio)->whereMonth('fecha', $mes)->pluck('fecha')
        );
    }

    private function sincronizarFechas($fechas): array
    {
        $resultado = ['ok' => 0, 'desincronizados' => 0, 'omitidos' => 0];

        foreach ($fechas->map(fn($f) => Carbon::parse($f)->toDateString())->unique() as $fecha) {
            $parcial = $this->verificarPorFecha($fecha);
            foreach ($resultado as $clave => $valor) {
                $resultado[$clave] += $parcial[$clave];
            }
        }

        $this->detectarTareasPendientes(); // recuenta y cierra lo corregido

        return $resultado;
    }

    private static function h(int $min): string
    {
        return intdiv($min, 60) . 'h' . ($min % 60 ? ' ' . str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT) . 'min' : '');
    }
}
