<?php

namespace App\Services\Planilla\Empleados;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Conversor de la estructura ANTIGUA de empleados (plan_empleados con datos personales, plan_contratos con todo,
 * plan_sueldos, plan_empleado_cargos) al Excel de empleados actual. Solo lee (otra conexión) y arma las filas:
 * - Cada contrato → su fila de contrato + una asignación y una remuneración desde su inicio.
 *   Cargo: el del historial de cargos vigente en el contrato; grupo: el del contrato.
 *   Sueldo: el último que empezó antes del fin del contrato (los sueldos solían empezar el 1 del mes).
 * - Pensión y cuenta de pago: una fila desde el inicio de cada contrato, solo cuando cambian.
 * - Planillas que ya no existen: general → oficina (se procesaba en la planilla oficina); mype y construccion → externa. Finalizado sin fecha de fin → fin = fecha en
 *   que se marcó finalizado.
 * Lo que se ajustó queda en avisosConversion() para revisarlo.
 */
class PlanillaEmpleadosLegadoDefinicion extends PlanillaEmpleadosDefinicion
{
    private ?ConnectionInterface $db = null;

    /** @var string[] */
    private array $ajustes = [];

    public function desde(ConnectionInterface $conexion): static
    {
        $this->db = $conexion;
        return $this;
    }

    /** @return string[] */
    public function avisosConversion(): array
    {
        return array_values(array_unique($this->ajustes));
    }

    public function filasSistema(): array
    {
        $db = $this->db ?? throw new \LogicException('Falta la conexión de la base antigua (desde()).');
        $this->ajustes = [];
        $sn = fn($v) => PlanillaEmpleadosReglas::sn($v);
        $fecha = fn($v) => $v && substr((string) $v, 0, 4) >= '1900' ? substr((string) $v, 0, 10) : null;
        $num = fn($v) => $v === null || (float) $v == 0.0 ? null : (float) $v;

        $empleados = $db->table('plan_empleados')->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombres')->get();
        $dni = $empleados->pluck('documento', 'id')->map(fn($d) => preg_replace('/\s+/', '', (string) $d));
        // Usuario del sistema de cada persona (solo informativo; las bases más antiguas no tienen users.persona_id)
        $usuarios = $db->getSchemaBuilder()->hasColumn('users', 'persona_id')
            ? $db->table('users')->whereNotNull('persona_id')->pluck('email', 'persona_id') : collect();
        $cargos = $db->table('plan_cargos')->pluck('nombre', 'id');
        $sueldos = $db->table('plan_sueldos')->orderBy('fecha_inicio')->get()->groupBy('plan_empleado_id');
        $cargosEmp = $db->table('plan_empleado_cargos')->orderBy('fecha_inicio')->get()->groupBy('plan_empleado_id');
        $contratos = $db->table('plan_contratos')->whereNull('deleted_at')->whereIn('plan_empleado_id', $dni->keys())
            ->orderBy('plan_empleado_id')->orderBy('fecha_inicio')->get();
        $nombre = $empleados->mapWithKeys(fn($e) => [$e->id => trim("{$e->apellido_paterno} {$e->apellido_materno}, {$e->nombres}")]);

        $filas = ['contratos' => [], 'asignaciones' => [], 'remuneraciones' => [], 'pensiones' => [], 'pagos' => []];
        $ultimaPension = [];
        $ultimoPago = [];
        foreach ($contratos as $c) {
            $d = $dni[$c->plan_empleado_id];
            $inicio = $fecha($c->fecha_inicio);
            $fin = $fecha($c->fecha_fin);
            $comentario = $c->comentario_cese;
            if (!$fin && $c->estado === 'finalizado') {
                $fin = substr((string) $c->updated_at, 0, 10);
                $comentario = trim(($comentario ? "{$comentario}. " : '') . 'Fin tomado de la fecha en que se marcó finalizado.');
                $this->ajustes[] = "Contrato de {$nombre[$c->plan_empleado_id]} ({$d}) desde {$inicio}: estaba finalizado sin fecha de fin; se puso {$fin}.";
            }
            // "general" se procesaba en la planilla oficina; mype y construcción no tienen planilla en el sistema
            $planilla = match ($c->tipo_planilla) {
                'agraria', 'oficina' => $c->tipo_planilla,
                'general' => 'oficina',
                default => 'externa',
            };
            if ($planilla !== $c->tipo_planilla) {
                $this->ajustes[] = "Contrato de {$nombre[$c->plan_empleado_id]} ({$d}) desde {$inicio}: planilla \"{$c->tipo_planilla}\" pasa a \"{$planilla}\".";
            }
            $filas['contratos'][] = [
                'dni' => $d, 'inicio' => $inicio, 'fin' => $fin, 'tipo_contrato' => $c->tipo_contrato, 'tipo_planilla' => $planilla,
                'tipo_ingreso' => $c->tipo_ingreso ?: 'planilla', 'fin_prueba' => $fecha($c->fecha_fin_prueba),
                'motivo_cese_sunat' => $c->motivo_cese_sunat, 'comentario_cese' => $comentario, 'anular' => 'NO',
            ];
            $corte = $fin ?? now()->toDateString();

            // Asignación: cargo vigente en el contrato (último que empezó antes del corte) y grupo del contrato
            $cargo = collect($cargosEmp[$c->plan_empleado_id] ?? [])->filter(fn($x) => substr($x->fecha_inicio, 0, 10) <= $corte)->last()
                ?? collect($cargosEmp[$c->plan_empleado_id] ?? [])->first();
            $filas['asignaciones'][] = ['dni' => $d, 'desde' => $inicio, 'cargo' => $cargo ? ($cargos[$cargo->plan_cargo_id] ?? null) : null,
                'grupo' => $c->grupo_codigo, 'motivo' => null, 'anular' => 'NO'];

            // Remuneración: último sueldo que empezó antes del corte + montos del contrato
            $sueldo = collect($sueldos[$c->plan_empleado_id] ?? [])->filter(fn($x) => substr($x->fecha_inicio, 0, 10) <= $corte)->last();
            $filas['remuneraciones'][] = [
                'dni' => $d, 'desde' => $inicio, 'sueldo' => $sueldo?->sueldo, 'remuneracion_basica' => $num($c->remuneracion_basica),
                'bonificacion' => $num($c->bonificacion), 'compensacion_vacacional' => $num($c->compensacion_vacacional),
                'modalidad_pago' => $c->modalidad_pago ?: 'mensual', 'beneficios_mensuales' => $sn($c->beneficios_mensuales ?? false),
                'motivo' => null, 'anular' => 'NO',
            ];

            // Pensión y cuenta de pago: solo cuando cambian respecto del contrato anterior del mismo empleado
            $pension = ['sistema_pension' => $c->plan_sp_codigo, 'esta_jubilado' => $sn($c->esta_jubilado), 'suspension_cuarta' => $sn($c->suspension_cuarta ?? false)];
            if (($ultimaPension[$d] ?? null) !== $pension) {
                $filas['pensiones'][] = ['dni' => $d, 'desde' => $inicio] + $pension + ['motivo' => null, 'anular' => 'NO'];
                $ultimaPension[$d] = $pension;
            }
            $pago = [];
            foreach (['metodo_pago', 'banco', 'tipo_cuenta', 'moneda_cuenta', 'numero_cuenta', 'banco_secundario', 'tipo_cuenta_secundaria',
                'moneda_cuenta_secundaria', 'numero_cuenta_secundaria'] as $campo) {
                $pago[$campo] = $c->{$campo} ?? null;
            }
            if (array_filter($pago) && ($ultimoPago[$d] ?? null) !== $pago) {
                $filas['pagos'][] = ['dni' => $d, 'desde' => $inicio] + $pago + ['motivo' => null, 'anular' => 'NO'];
                $ultimoPago[$d] = $pago;
            }
        }

        return [
            'grupos' => $db->table('plan_grupos')->orderBy('codigo')->get()->map(fn($g) => ['codigo' => $g->codigo, 'descripcion' => $g->descripcion, 'color' => $g->color])->all(),
            'cargos' => $db->table('plan_cargos')->orderBy('nombre')->get()->map(fn($c) => [
                'nombre' => $c->nombre, 'cupo_maximo' => $c->cupo_maximo, 'activo' => $sn($c->activo && !$c->deleted_at),
            ])->all(),
            'empleados' => $empleados->map(fn($e) => [
                'dni' => $dni[$e->id], 'apellido_paterno' => $e->apellido_paterno, 'apellido_materno' => $e->apellido_materno, 'nombres' => $e->nombres,
                'fecha_nacimiento' => $fecha($e->fecha_nacimiento), 'genero' => $e->genero, 'email' => $e->email ? mb_strtolower($e->email) : null,
                'telefono' => $e->numero, 'direccion' => $e->direccion, 'orden' => $e->orden, 'comentarios' => $e->comentarios,
                'activo' => $sn(!$e->deleted_at), 'usuario' => $e->persona_id ? ($usuarios[$e->persona_id] ?? null) : null,
            ])->all(),
        ] + $filas + [
            'derecho_habientes' => $db->table('empleado_derecho_habientes as v')->join('derecho_habientes as d', 'd.id', '=', 'v.derecho_habiente_id')
                ->whereIn('v.empleado_id', $dni->keys())->whereNull('d.deleted_at')->orderBy('v.empleado_id')->orderBy('d.fecha_nacimiento')
                ->get(['v.empleado_id', 'v.rol', 'v.activo', 'd.*'])->map(fn($d) => [
                    'dni' => $dni[$d->empleado_id], 'rol' => $d->rol, 'documento' => $d->documento, 'tipo_documento' => $d->tipo_documento,
                    'nombres' => $d->nombres, 'fecha_nacimiento' => $fecha($d->fecha_nacimiento), 'tipo' => $d->tipo,
                    'discapacidad_severa' => $sn($d->discapacidad_severa), 'pension_no_contributiva' => $sn($d->percibe_pension_no_contributiva),
                    'inicio_estudios' => $fecha($d->fecha_inicio_estudios), 'fin_estudios' => $fecha($d->fecha_fin_estudios), 'activo' => $sn($d->activo),
                ])->all(),
        ];
    }
}
