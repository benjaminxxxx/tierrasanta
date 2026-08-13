<?php
namespace App\Domain\DerechoHabiente;

use App\Models\EmpleadoDerechoHabiente;
use App\Models\PlanEmpleado;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
final class EmpleadoAsignacionFamiliarService
{
    public function vigenteDesde(int $empleadoId): ?string
    {
        $minimo = EmpleadoDerechoHabiente::where('empleado_id', $empleadoId)
            ->where('activo', true)
            ->whereHas('derechoHabiente', fn($q) => $q->where('tipo', 'hijo'))
            ->whereNotNull('anio_vigencia')
            ->orderBy('anio_vigencia')->orderBy('mes_vigencia')
            ->first();

        return $minimo ? sprintf('%02d/%d', $minimo->mes_vigencia, $minimo->anio_vigencia) : null;
    }

    public function calcula(int $empleadoId, int $mes, int $anio): bool
    {
        return $this->calificantes($empleadoId, $mes, $anio)->isNotEmpty();
    }

    public function explicar(int $empleadoId, int $mes, int $anio): array
    {
        $vinculos = EmpleadoDerechoHabiente::with('derechoHabiente')
            ->where('empleado_id', $empleadoId)
            ->where('activo', true)
            ->whereHas('derechoHabiente', fn($q) => $q->where('tipo', 'hijo'))
            ->get();

        if ($vinculos->isEmpty()) {
            return ['tiene' => false, 'motivos' => ['No tiene hijos registrados activos.']];
        }

        $motivos = [];
        $algunoCalifica = false;

        foreach ($vinculos as $v) {
            $dh = $v->derechoHabiente;
            $edad = $dh->fecha_nacimiento->diff(now()->setDay(1)->setMonth($mes)->setYear($anio))->y;
            $estudiando = $v->fecha_inicio_estudios
                && $v->fecha_inicio_estudios <= now()
                && (!$v->fecha_fin_estudios || $v->fecha_fin_estudios >= now());

            if ($dh->discapacidad_severa) {
                $algunoCalifica = true;
                $motivos[] = "{$dh->nombres}: califica por discapacidad severa (sin límite de edad).";
            } elseif ($edad < 18) {
                $algunoCalifica = true;
                $motivos[] = "{$dh->nombres}: {$edad} años, menor de edad.";
            } elseif ($edad < 24 && $estudiando) {
                $algunoCalifica = true;
                $motivos[] = "{$dh->nombres}: {$edad} años, estudios vigentes.";
            } elseif ($edad >= 18 && !$estudiando) {
                $motivos[] = "{$dh->nombres}: {$edad} años, sin estudios superiores vigentes registrados.";
            }
        }

        return ['tiene' => $algunoCalifica, 'motivos' => $motivos];
    }

    /**
     * Núcleo de la regla de negocio. Recibe una colección de EmpleadoDerechoHabiente
     * (ya filtrada a tipo=hijo, activo=true) y evalúa cada uno para el mes/año dado.
     */
    public function evaluarColeccion(Collection $vinculosHijos, int $mes, int $anio): array
    {
        $corte = Carbon::create($anio, $mes, 1)->endOfMonth();

        if ($vinculosHijos->isEmpty()) {
            return ['tiene' => false, 'detalle' => []];
        }

        $detalle = [];
        $algunoCalifica = false;

        foreach ($vinculosHijos as $v) {
            $dh = $v->derechoHabiente;
            $edadContable = $dh->fecha_nacimiento->diffInYears($corte);
            $vigente = ($v->anio_vigencia < $anio) || ($v->anio_vigencia === $anio && $v->mes_vigencia <= $mes);

            $fila = [
                'nombres' => $dh->nombres,
                'documento' => $dh->documento,
                'edad' => $edadContable,
                'discapacidad_severa' => $dh->discapacidad_severa,
                'califica' => false,
                'motivo' => '',
            ];

            if (!$vigente) {
                $fila['motivo'] = "Aún no vigente para este empleador (desde {$v->mes_vigencia}/{$v->anio_vigencia}).";
                $detalle[] = $fila;
                continue;
            }

            if ($dh->discapacidad_severa) {
                if ($dh->percibe_pension_no_contributiva) {
                    $fila['motivo'] = 'Discapacidad severa certificada, pero percibe Pensión No Contributiva (Ley 29973) — no genera asignación.';
                } else {
                    $fila['califica'] = true;
                    $algunoCalifica = true;
                    $fila['motivo'] = 'Califica por discapacidad severa certificada, sin límite de edad.';
                }
                $detalle[] = $fila;
                continue;
            }

            if ($edadContable < 18) {
                $fila['califica'] = true;
                $algunoCalifica = true;
                $fila['motivo'] = 'Menor de edad.';
                $detalle[] = $fila;
                continue;
            }

            $estudiaVigente = $dh->fecha_inicio_estudios
                && $dh->fecha_inicio_estudios <= $corte
                && (!$dh->fecha_fin_estudios || $dh->fecha_fin_estudios >= $corte);

            if ($estudiaVigente) {
                $fila['califica'] = true;
                $algunoCalifica = true;
                $fila['motivo'] = 'Mayor de edad con estudios superiores vigentes.';
            } else {
                $fin = $dh->fecha_fin_estudios
                    ? " Finalizó estudios el {$dh->fecha_fin_estudios->format('d/m/Y')}."
                    : ' No registra estudios superiores.';
                $fila['motivo'] = "Mayor de edad, sin discapacidad severa.{$fin} No corresponde asignación.";
            }

            $detalle[] = $fila;
        }

        return ['tiene' => $algunoCalifica, 'detalle' => $detalle];
    }

    public function explicarEmpleado(int $empleadoId, int $mes, int $anio): array
    {
        $vinculos = EmpleadoDerechoHabiente::with('derechoHabiente')
            ->where('empleado_id', $empleadoId)
            ->where('activo', true)
            ->whereHas('derechoHabiente', fn($q) => $q->where('tipo', 'hijo'))
            ->get();

        return $this->evaluarColeccion($vinculos, $mes, $anio);
    }

    public function calculaEmpleado(int $empleadoId, int $mes, int $anio): bool
    {
        return $this->explicarEmpleado($empleadoId, $mes, $anio)['tiene'];
    }

    /**
     * Resumen para las tarjetas de estadísticas de un mes/año específico.
     */
    public function resumenMes(int $mes, int $anio): array
    {
        $corte = Carbon::create($anio, $mes, 1)->endOfMonth();

        $empleados = PlanEmpleado::whereHas('derechoHabientes', function ($q) {
            $q->where('activo', true)->whereHas('derechoHabiente', fn($qq) => $qq->where('tipo', 'hijo'));
        })
            ->with(['derechoHabientes' => fn($q) => $q->where('activo', true)->with('derechoHabiente')])
            ->get();

        $conAsignacion = 0;
        foreach ($empleados as $empleado) {
            $hijos = $empleado->derechoHabientes->filter(fn($v) => $v->derechoHabiente->tipo === 'hijo');
            if ($this->evaluarColeccion($hijos, $mes, $anio)['tiene']) {
                $conAsignacion++;
            }
        }

        $entranVigencia = EmpleadoDerechoHabiente::where('activo', true)
            ->where('mes_vigencia', $mes)->where('anio_vigencia', $anio)
            ->whereHas('derechoHabiente', fn($q) => $q->where('tipo', 'hijo'))
            ->distinct('empleado_id')->count('empleado_id');

        $finalizanEstudios = EmpleadoDerechoHabiente::where('activo', true)
            ->whereHas('derechoHabiente', function ($q) use ($corte) {
                $q->where('tipo', 'hijo')
                    ->whereNotNull('fecha_fin_estudios')
                    ->whereBetween('fecha_fin_estudios', [$corte->copy()->startOfMonth(), $corte]);
            })
            ->distinct('empleado_id')->count('empleado_id');

        return [
            'con_asignacion' => $conAsignacion,
            'total_con_hijos_registrados' => $empleados->count(),
            'entran_vigencia' => $entranVigencia,
            'finalizan_estudios' => $finalizanEstudios,
        ];
    }
}