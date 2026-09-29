<?php

namespace App\Services\Planilla\Empleado;

use App\Models\EmpleadoDerechoHabiente;
use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanEmpleadoCargo;
use App\Models\PlanSueldo;
use App\Models\PlanSuspension;
use Carbon\Carbon;

/**
 * Arma el perfil del trabajador: resumen de su estado actual, evolución del sueldo y una
 * línea de tiempo con ingresos, ceses, reingresos, cargos, sueldos y suspensiones.
 */
class EmpleadoPerfilServicio
{
    public const MOTIVOS_CESE = [
        '01' => 'Renuncia',
        '02' => 'Despido',
    ];

    public function construir(int $empleadoId): array
    {
        $empleado = PlanEmpleado::withTrashed()->with('persona')->findOrFail($empleadoId);
        $hoy = now()->startOfDay();

        $contratos = PlanContrato::with(['grupo', 'descuento'])
            ->where('plan_empleado_id', $empleadoId)
            ->orderBy('fecha_inicio')
            ->get();
        $cargos = PlanEmpleadoCargo::with('cargo')->where('plan_empleado_id', $empleadoId)->orderBy('fecha_inicio')->get();
        $sueldos = PlanSueldo::where('plan_empleado_id', $empleadoId)->orderBy('fecha_inicio')->get();
        $suspensiones = PlanSuspension::with('tipoSuspension')->where('plan_empleado_id', $empleadoId)->orderBy('fecha_inicio')->get();
        $familiares = EmpleadoDerechoHabiente::with('derechoHabiente')->where('empleado_id', $empleadoId)->get();

        $eventos = [];
        $agregar = function (string $fecha, string $tipo, string $titulo, ?string $detalle = null, array $extra = []) use (&$eventos) {
            $eventos[] = ['fecha' => Carbon::parse($fecha)->toDateString(), 'tipo' => $tipo, 'titulo' => $titulo, 'detalle' => $detalle] + $extra;
        };

        // ── Contratos: ingreso, reingreso, renovación, fin de prueba y cese ──
        $finAnterior = null;
        foreach ($contratos as $i => $c) {
            $inicio = Carbon::parse($c->fecha_inicio);
            $detalle = collect([
                mb_strtoupper($c->tipo_contrato),
                $c->tipo_planilla ? 'Planilla ' . $c->tipo_planilla : null,
                $c->grupo_codigo ? 'Grupo ' . $c->grupo_codigo : null,
                $c->modalidad_pago ? 'Pago ' . $c->modalidad_pago : null,
                $c->descuento?->codigo ?? 'No afiliado',
            ])->filter()->implode(' · ');

            if ($i === 0) {
                $agregar($inicio, 'ingreso', 'Ingreso a la empresa', $detalle);
            } elseif ($finAnterior && $finAnterior->diffInDays($inicio) > 1) { // Carbon 3: diff con signo
                $meses = (int) round($finAnterior->diffInMonths($inicio, true));
                $agregar($inicio, 'reingreso', 'Reingreso (nuevo contrato)', $detalle, [
                    'nota' => $meses > 0 ? "Volvió después de {$meses} mes(es) fuera" : 'Volvió después de ' . (int) $finAnterior->diffInDays($inicio) . ' día(s) fuera',
                ]);
            } else {
                $agregar($inicio, 'renovacion', 'Renovación de contrato', $detalle);
            }

            if ($c->fecha_fin_prueba) {
                $finPrueba = Carbon::parse($c->fecha_fin_prueba);
                $agregar($finPrueba, 'prueba', $finPrueba->gt($hoy) ? 'Termina su periodo de prueba' : 'Superó el periodo de prueba', null, ['futuro' => $finPrueba->gt($hoy)]);
            }

            if ($c->fecha_fin) {
                $fin = Carbon::parse($c->fecha_fin);
                $motivo = self::MOTIVOS_CESE[$c->motivo_cese_sunat] ?? ($c->motivo_cese_sunat ? "Motivo SUNAT {$c->motivo_cese_sunat}" : null);
                $agregar($fin, $fin->gt($hoy) ? 'fin_programado' : 'cese',
                    $fin->gt($hoy) ? 'Fin de contrato programado' : 'Cese' . ($motivo ? " — {$motivo}" : ''),
                    $c->comentario_cese ?: $c->motivo_despido,
                    ['futuro' => $fin->gt($hoy)]
                );
                $finAnterior = $fin;
            } else {
                $finAnterior = null;
            }
        }

        // ── Cargos ──
        foreach ($cargos as $cargo) {
            $agregar($cargo->fecha_inicio, 'cargo', 'Cargo: ' . ($cargo->cargo?->nombre ?? '—'),
                collect([$cargo->motivo_cambio ? ucfirst($cargo->motivo_cambio) : null, $cargo->grupo_codigo ? "Grupo {$cargo->grupo_codigo}" : null])->filter()->implode(' · ') ?: null);
            if ($cargo->fecha_fin) {
                $agregar($cargo->fecha_fin, 'cargo_fin', 'Dejó el cargo ' . ($cargo->cargo?->nombre ?? ''));
            }
        }

        // ── Sueldos (con variación respecto al anterior) ──
        $anterior = null;
        foreach ($sueldos as $s) {
            $monto = (float) $s->sueldo;
            $variacion = $anterior ? $monto - $anterior : null;
            $agregar($s->fecha_inicio, 'sueldo', $anterior === null ? 'Sueldo inicial' : ($variacion >= 0 ? 'Aumento de sueldo' : 'Reducción de sueldo'), null, [
                'monto' => $monto,
                'variacion' => $variacion,
                'porcentaje' => $anterior ? round($variacion / $anterior * 100, 1) : null,
            ]);
            $anterior = $monto;
        }

        // ── Suspensiones ──
        foreach ($suspensiones as $sus) {
            $agregar($sus->fecha_inicio, 'suspension', 'Suspensión: ' . ($sus->tipoSuspension?->descripcion ?? '—'),
                trim(($sus->fecha_fin ? 'Hasta ' . Carbon::parse($sus->fecha_fin)->format('d/m/Y') : 'Sin fecha de fin') . ($sus->observaciones ? ' · ' . $sus->observaciones : '')));
        }

        // ── Derecho habientes ──
        foreach ($familiares as $f) {
            if ($f->anio_vigencia && $f->mes_vigencia) {
                $agregar(sprintf('%04d-%02d-01', $f->anio_vigencia, $f->mes_vigencia), 'familiar',
                    'Derecho habiente: ' . ($f->derechoHabiente?->nombres ?? '—'), ucfirst((string) $f->rol) . ($f->activo ? '' : ' · inactivo'));
            }
        }

        if ($empleado->deleted_at) {
            $agregar($empleado->deleted_at, 'eliminado', 'Registro enviado a Eliminados');
        }

        // Más reciente primero; a igual fecha, el cese antes que el ingreso siguiente
        $orden = ['fin_programado' => 0, 'eliminado' => 1, 'cese' => 2, 'cargo_fin' => 3, 'prueba' => 4, 'suspension' => 5, 'sueldo' => 6, 'cargo' => 7, 'familiar' => 8, 'renovacion' => 9, 'reingreso' => 10, 'ingreso' => 11];
        usort($eventos, fn($a, $b) => [$b['fecha'], $orden[$a['tipo']] ?? 99] <=> [$a['fecha'], $orden[$b['tipo']] ?? 99]);

        $resumen = $this->resumen($empleado, $contratos, $cargos, $sueldos, $hoy);
        $resumen['reingresos'] = count(array_filter($eventos, fn($e) => $e['tipo'] === 'reingreso'));

        return [
            'empleado' => $empleado,
            'resumen' => $resumen,
            'sueldos' => $sueldos->map(fn($s) => ['fecha' => Carbon::parse($s->fecha_inicio)->format('m/Y'), 'monto' => (float) $s->sueldo])->values()->all(),
            'eventos' => $eventos,
        ];
    }

    private function resumen(PlanEmpleado $empleado, $contratos, $cargos, $sueldos, Carbon $hoy): array
    {
        $vigente = $contratos->last(fn($c) => Carbon::parse($c->fecha_inicio)->lte($hoy) && (!$c->fecha_fin || Carbon::parse($c->fecha_fin)->gte($hoy)));
        $ultimo = $contratos->last();

        // Antigüedad efectiva: suma de días dentro de contratos (sin contar los periodos fuera)
        $dias = $contratos->sum(function ($c) use ($hoy) {
            $inicio = Carbon::parse($c->fecha_inicio);
            $fin = $c->fecha_fin ? Carbon::parse($c->fecha_fin)->min($hoy) : $hoy;
            return $inicio->lte($fin) ? (int) $inicio->diffInDays($fin) + 1 : 0;
        });

        $estado = match (true) {
            (bool) $empleado->deleted_at => ['Eliminado', 'rojo'],
            (bool) $vigente => ['Contrato vigente', 'verde'],
            $ultimo && Carbon::parse($ultimo->fecha_inicio)->gt($hoy) => ['Contrato por iniciar', 'azul'],
            (bool) $ultimo => ['Sin contrato vigente (cesado)', 'ambar'],
            default => ['Sin contratos registrados', 'gris'],
        };

        $contratoMostrado = $vigente ?? $ultimo;
        $cargoActual = $cargos->last(fn($c) => !$c->fecha_fin);
        $sueldoActual = $sueldos->last(fn($s) => Carbon::parse($s->fecha_inicio)->lte($hoy));

        return [
            'estado' => $estado[0],
            'estado_color' => $estado[1],
            'primer_ingreso' => $contratos->first()?->fecha_inicio,
            'antiguedad_dias' => $dias,
            'antiguedad_texto' => $this->textoDuracion($dias),
            'contratos' => $contratos->count(),
            'contrato' => $contratoMostrado,
            'grupo' => $contratoMostrado?->grupo,
            'cargo' => $cargoActual?->cargo?->nombre,
            'sueldo' => $sueldoActual ? (float) $sueldoActual->sueldo : null,
            'edad' => $empleado->fecha_nacimiento ? Carbon::parse($empleado->fecha_nacimiento)->age : null,
        ];
    }

    private function textoDuracion(int $dias): string
    {
        if ($dias <= 0) {
            return '—';
        }
        $anios = intdiv($dias, 365);
        $meses = intdiv($dias % 365, 30);
        return collect([$anios ? "{$anios} año(s)" : null, $meses ? "{$meses} mes(es)" : null])->filter()->implode(' ') ?: "{$dias} día(s)";
    }
}
