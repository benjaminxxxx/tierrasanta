<?php

namespace App\Services\Riego;

use App\Models\ConsolidadoRiego;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualDetalle;
use App\Models\PlanRegistroDiario;
use App\Models\TareaPendiente;
use App\Services\Sistema\TareasPendientes\TareaPendienteServicio;
use Illuminate\Support\Carbon;

class VerificacionSincronizacionRiegoServicio
{
    public function verificarPorFecha(string $fecha): array
    {
        $consolidados = ConsolidadoRiego::whereDate('fecha', $fecha)->get();
        $resultado = ['ok' => 0, 'desincronizados' => 0, 'omitidos' => 0];

        foreach ($consolidados as $item) {

            // Cuadrilla: "aún no desarrollado, innecesario por el momento" — se omite de la verificación
            if ($item->trabajador_type !== PlanEmpleado::class) {
                $resultado['omitidos']++;
                continue;
            }

            $mes = Carbon::parse($fecha)->month;
            $anio = Carbon::parse($fecha)->year;

            $detalleMensual = PlanMensualDetalle::where('plan_empleado_id', $item->trabajador_id)
                ->whereHas('planillaMensual', fn($q) => $q->where('mes', $mes)->where('anio', $anio))
                ->first();

            if (!$detalleMensual) {
                $item->update(['sincronizado' => false]);
                $resultado['desincronizados']++;
                continue;
            }

            $registroDiario = PlanRegistroDiario::where('plan_det_men_id', $detalleMensual->id)
                ->whereDate('fecha', $fecha)
                ->first();

            $horasEnPlanilla = $registroDiario->total_horas ?? 0;
            $horasEnRiego = round($item->minutos_jornal / 60, 2);

            $coincide = abs($horasEnPlanilla - $horasEnRiego) < 0.05; // tolerancia por redondeo

            $item->update(['sincronizado' => $coincide]);
            $coincide ? $resultado['ok']++ : $resultado['desincronizados']++;
        }

        return $resultado;
    }

    /**
     * Verifica un día (pantalla de reporte diario de riego) y deja la tarea pendiente al día,
     * para que el panel de tareas muestre el conteo real sin tener que volver a detectar.
     */
    public function verificarFechaYActualizarTareas(string $fecha): array
    {
        $resultado = $this->verificarPorFecha($fecha);
        $this->detectarTareasPendientes();

        return $resultado;
    }

    /**
     * Detecta/actualiza las tareas pendientes de riego desincronizado,
     * agrupadas por mes. Se puede llamar manualmente o desde un scheduler.
     */
    public function detectarTareasPendientes(?string $fechaInicio = null, ?string $fechaFin = null): void
    {
        // Revisa todos los meses (la tarea general resume el total); cada mes lleva su periodo
        // para que el panel lo muestre al elegir ese mes.
        $desincronizados = ConsolidadoRiego::where('sincronizado', false)
            ->where('trabajador_type', PlanEmpleado::class) // mismo criterio que verificarPorFecha
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->fecha)->format('Y-m'));
       
        $registrador = app(TareaPendienteServicio::class);

        $tareaPadre = $registrador->registrarOActualizar([
            'tipo' => 'riego-desincronizado',
            'clave' => null,
            'titulo' => 'Registros de riego desincronizados',
            'descripcion' => 'Hay registros de riego cuyas horas no coinciden con el registro diario de planilla.',
            'variante' => 'warning',
            'cantidad_afectados' => $desincronizados->flatten()->count(),
            'servicio' => self::class,
            'metodo_detectar' => 'detectarTareasPendientes',
            'acciones' => [
                ['titulo' => 'Sincronizar todos', 'metodo' => 'sincronizarTodos', 'parametros' => []],
            ],
        ]);

        foreach ($desincronizados as $mesClave => $items) {
            [$anio, $mes] = explode('-', $mesClave);
            $nombreMes = Carbon::create((int) $anio, (int) $mes, 1)->translatedFormat('F');

            $inicioMes = Carbon::create((int) $anio, (int) $mes, 1);

            $registrador->registrarOActualizar([
                'tipo' => 'riego-desincronizado',
                'clave' => $mesClave,
                'fecha_inicio' => $inicioMes->toDateString(),
                'fecha_fin' => $inicioMes->copy()->endOfMonth()->toDateString(),
                'parent_id' => $tareaPadre?->id,
                'titulo' => "Riego desincronizado — {$nombreMes} {$anio}",
                'descripcion' => "{$items->count()} registro(s) sin sincronizar.",
                'variante' => 'warning',
                'cantidad_afectados' => $items->count(),
                'servicio' => self::class,
                'metodo_detectar' => 'detectarTareasPendientes',
                'acciones' => [
                    ['titulo' => "Sincronizar {$nombreMes}", 'metodo' => 'sincronizarMes', 'parametros' => ['mes' => (int) $mes, 'anio' => (int) $anio]],
                ],
            ]);
        }

        // Meses que ya no tienen registros desincronizados (se corrigieron día por día, desde la
        // pantalla de riego o la planilla): se cierran. Sin esto su tarea quedaba con el conteo viejo.
        TareaPendiente::where('tipo', 'riego-desincronizado')
            ->where('estado', 'pendiente')
            ->whereNotNull('clave')
            ->whereNotIn('clave', $desincronizados->keys()->all())
            ->get()
            ->each(fn($vieja) => $registrador->registrarOActualizar([
                'tipo' => 'riego-desincronizado',
                'clave' => $vieja->clave,
                'cantidad_afectados' => 0,
            ]));
    }

    /** ⚠️ Ver pregunta abajo sobre qué debe hacer realmente "sincronizar" */
    public function sincronizarTodos(): array
    {
        return $this->sincronizarFechas(
            ConsolidadoRiego::where('sincronizado', false)->where('trabajador_type', PlanEmpleado::class)->pluck('fecha')->unique()
        );
    }

    public function sincronizarMes(int $mes, int $anio): array
    {
        return $this->sincronizarFechas(
            ConsolidadoRiego::where('sincronizado', false)
                ->where('trabajador_type', PlanEmpleado::class)
                ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
                ->pluck('fecha')->unique()
        );
    }

    private function sincronizarFechas($fechas): array
    {
        $resultado = ['ok' => 0, 'desincronizados' => 0, 'omitidos' => 0];

        foreach ($fechas as $fecha) {
            $parcial = $this->verificarPorFecha($fecha);
            foreach ($resultado as $clave => $valor) {
                $resultado[$clave] += $parcial[$clave];
            }
        }

        $this->detectarTareasPendientes(); // recuenta y cierra solo si llegó a 0

        return $resultado;
    }
}
