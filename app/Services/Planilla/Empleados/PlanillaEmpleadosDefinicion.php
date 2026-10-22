<?php

namespace App\Services\Planilla\Empleados;

use App\Constants\Permisos;
use App\Models\PlanCargo;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelDefinicion;
use App\Services\Sistema\Vigencia\SistemaVigenciaReglas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Excel de empleados (motor común SistemaLibroExcel), solo planilla: grupos, cargos, empleados (crea / actualiza su
 * persona con PlanillaEmpleadoServicio), contratos, sueldos, historial de cargos y derecho habientes.
 * Nunca borra: Activo = NO da de baja al empleado o inactiva el vínculo con el familiar.
 */
class PlanillaEmpleadosDefinicion extends SistemaLibroExcelDefinicion
{
    /** @var string[] avisos de la lectura (vigencia) */
    private array $avisos = [];

    public function reglas(): string
    {
        return PlanillaEmpleadosReglas::class;
    }

    public function permiso(): string
    {
        return Permisos::PERSONAL_CONTRATOS;
    }

    public function leeme(): array
    {
        $vigencia = SistemaVigenciaReglas::desde();
        return array_filter([
            'El empleado se reconoce por su DNI (8 dígitos) en todas las hojas.',
            'Contratos, sueldos y cargos: un periodo por fila (Desde / Hasta). Hasta vacío = vigente.',
            $vigencia ? 'Vigencia del sistema: ' . Carbon::parse($vigencia)->format('d/m/Y') . '. Al subir, lo que terminó antes no se lee y lo vigente empieza en esa fecha.' : null,
            'Derecho habientes: una fila por familiar y empleado ("El empleado es" padre, madre, cónyuge o tutor).',
            'Sistema de pensión: código de la hoja "Descuentos SP" del Excel de recursos (SNP, HAB F, PRI M…).',
        ]);
    }

    // ------------------------------------------------------------------ filas del sistema

    public function filasSistema(): array
    {
        $empleados = DB::table('plan_empleados')->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombres')->get();
        $dni = $empleados->pluck('documento', 'id');
        $cargos = DB::table('plan_cargos')->pluck('nombre', 'id');
        $sn = fn($v) => PlanillaEmpleadosReglas::sn($v);
        // Fechas imposibles (0001-01-01…) se exportan vacías: Excel no las representa
        $fecha = fn($v) => $v && substr((string) $v, 0, 4) >= '1900' ? substr((string) $v, 0, 10) : null;

        return [
            'grupos' => DB::table('plan_grupos')->orderBy('codigo')->get()->map(fn($g) => ['codigo' => $g->codigo, 'descripcion' => $g->descripcion, 'color' => $g->color])->all(),
            'cargos' => DB::table('plan_cargos')->orderBy('nombre')->get()->map(fn($c) => [
                'nombre' => $c->nombre, 'cupo_maximo' => $c->cupo_maximo, 'activo' => $sn($c->activo && !$c->deleted_at),
            ])->all(),
            'empleados' => $empleados->map(fn($e) => [
                'dni' => $e->documento, 'apellido_paterno' => $e->apellido_paterno, 'apellido_materno' => $e->apellido_materno, 'nombres' => $e->nombres,
                'fecha_nacimiento' => $fecha($e->fecha_nacimiento), 'genero' => $e->genero, 'email' => $e->email, 'telefono' => $e->numero,
                'direccion' => $e->direccion, 'fecha_ingreso' => $fecha($e->fecha_ingreso), 'orden' => $e->orden, 'comentarios' => $e->comentarios,
                'activo' => $sn(!$e->deleted_at),
            ])->all(),
            'contratos' => DB::table('plan_contratos')->whereNull('deleted_at')->whereIn('plan_empleado_id', $dni->keys())
                ->orderBy('plan_empleado_id')->orderBy('fecha_inicio')->get()->map(fn($c) => [
                    'dni' => $dni[$c->plan_empleado_id], 'desde' => $fecha($c->fecha_inicio), 'hasta' => $fecha($c->fecha_fin),
                    'tipo_contrato' => $c->tipo_contrato, 'tipo_planilla' => $c->tipo_planilla, 'tipo_ingreso' => $c->tipo_ingreso, 'grupo' => $c->grupo_codigo,
                    'estado' => $c->estado, 'remuneracion_basica' => $c->remuneracion_basica, 'bonificacion' => $c->bonificacion,
                    'compensacion_vacacional' => $c->compensacion_vacacional, 'sistema_pension' => $c->plan_sp_codigo, 'esta_jubilado' => $sn($c->esta_jubilado),
                    'suspension_cuarta' => $sn($c->suspension_cuarta), 'beneficios_mensuales' => $sn($c->beneficios_mensuales),
                    'modalidad_pago' => $c->modalidad_pago, 'metodo_pago' => $c->metodo_pago, 'banco' => $c->banco, 'tipo_cuenta' => $c->tipo_cuenta,
                    'moneda_cuenta' => $c->moneda_cuenta, 'numero_cuenta' => $c->numero_cuenta, 'banco_secundario' => $c->banco_secundario,
                    'tipo_cuenta_secundaria' => $c->tipo_cuenta_secundaria, 'moneda_cuenta_secundaria' => $c->moneda_cuenta_secundaria,
                    'numero_cuenta_secundaria' => $c->numero_cuenta_secundaria, 'fecha_fin_prueba' => $fecha($c->fecha_fin_prueba),
                    'motivo_cese_sunat' => $c->motivo_cese_sunat, 'comentario_cese' => $c->comentario_cese,
                ])->all(),
            'sueldos' => DB::table('plan_sueldos')->whereIn('plan_empleado_id', $dni->keys())->orderBy('plan_empleado_id')->orderBy('fecha_inicio')->get()
                ->map(fn($s) => ['dni' => $dni[$s->plan_empleado_id], 'desde' => $fecha($s->fecha_inicio), 'hasta' => $fecha($s->fecha_fin), 'sueldo' => $s->sueldo])->all(),
            'cargos_empleado' => DB::table('plan_empleado_cargos')->whereIn('plan_empleado_id', $dni->keys())->orderBy('plan_empleado_id')->orderBy('fecha_inicio')->get()
                ->map(fn($c) => [
                    'dni' => $dni[$c->plan_empleado_id], 'cargo' => $cargos[$c->plan_cargo_id] ?? null, 'grupo' => $c->grupo_codigo,
                    'desde' => $fecha($c->fecha_inicio), 'hasta' => $fecha($c->fecha_fin), 'motivo' => $c->motivo_cambio,
                ])->all(),
            'derecho_habientes' => DB::table('empleado_derecho_habientes as v')->join('derecho_habientes as d', 'd.id', '=', 'v.derecho_habiente_id')
                ->whereIn('v.empleado_id', $dni->keys())->whereNull('d.deleted_at')->orderBy('v.empleado_id')->orderBy('d.fecha_nacimiento')
                ->get(['v.empleado_id', 'v.rol', 'v.activo', 'd.*'])->map(fn($d) => [
                    'dni' => $dni[$d->empleado_id], 'rol' => $d->rol, 'documento' => $d->documento, 'tipo_documento' => $d->tipo_documento,
                    'nombres' => $d->nombres, 'fecha_nacimiento' => $fecha($d->fecha_nacimiento), 'tipo' => $d->tipo,
                    'discapacidad_severa' => $sn($d->discapacidad_severa), 'pension_no_contributiva' => $sn($d->percibe_pension_no_contributiva),
                    'inicio_estudios' => $fecha($d->fecha_inicio_estudios), 'fin_estudios' => $fecha($d->fecha_fin_estudios), 'activo' => $sn($d->activo),
                ])->all(),
        ];
    }

    // ------------------------------------------------------------------ análisis

    /**
     * Vigencia del sistema: en contratos, sueldos y cargos, lo que terminó antes no se lee y lo vigente empieza en la
     * fecha de vigencia (contrato simulado de la empresa nueva). Sin vigencia (dominio antiguo) no cambia nada.
     */
    public function prepararHoja(string $hoja, array $filas): array
    {
        $vigencia = SistemaVigenciaReglas::desde();
        if (!$vigencia || !in_array($hoja, PlanillaEmpleadosReglas::HOJAS_CON_PERIODO, true)) {
            return $filas;
        }
        $titulo = PlanillaEmpleadosReglas::HOJAS[$hoja][0];
        $omitidas = 0;
        $ajustadas = 0;
        $r = [];
        foreach ($filas as $f) {
            if ($f['hasta'] !== null && PlanillaEmpleadosReglas::esFecha($f['hasta']) && $f['hasta'] < $vigencia) {
                $omitidas++;
                continue;
            }
            if ($f['desde'] !== null && PlanillaEmpleadosReglas::esFecha($f['desde']) && $f['desde'] < $vigencia) {
                $f['desde'] = $vigencia;
                $ajustadas++;
            }
            $r[] = $f;
        }
        $fecha = Carbon::parse($vigencia)->format('d/m/Y');
        if ($omitidas) {
            $this->avisos[] = "{$titulo}: {$omitidas} terminaron antes de la vigencia ({$fecha}) y no se leen.";
        }
        if ($ajustadas) {
            $this->avisos[] = "{$titulo}: {$ajustadas} vigentes empiezan el {$fecha} (vigencia del sistema).";
        }
        return $r;
    }

    public function avisosLectura(): array
    {
        $a = $this->avisos;
        $this->avisos = [];
        return $a;
    }

    public function prepararFila(string $hoja, array $f): array
    {
        foreach (['dni', 'documento'] as $c) {
            if (isset($f[$c]) && $f[$c] !== null) {
                $f[$c] = preg_replace('/\s+/', '', $f[$c]);
            }
        }
        if ($hoja === 'empleados' && $f['email'] !== null) {
            $f['email'] = mb_strtolower($f['email']);
        }
        if ($hoja === 'empleados' && $f['genero'] !== null) {
            $f['genero'] = mb_strtoupper($f['genero']);
        }
        return $f;
    }

    public function reglasFila(string $hoja, array $f, array &$avisos, array $contexto): array
    {
        $e = [];
        if ($hoja === 'empleados') {
            if ($f['dni'] !== null && !preg_match('/^\d{8}$/', $f['dni'])) {
                $e[] = 'El DNI debe tener 8 dígitos.';
            }
            if ($f['email'] !== null && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
                $e[] = "Correo \"{$f['email']}\" no es válido.";
            }
            if ($f['email'] !== null && DB::table('plan_empleados')->where('email', $f['email'])->where('documento', '<>', $f['dni'])->exists()) {
                $e[] = "El correo \"{$f['email']}\" ya lo tiene otro empleado.";
            }
            foreach (['fecha_nacimiento' => 'La fecha de nacimiento', 'fecha_ingreso' => 'La fecha de ingreso'] as $c => $t) {
                if ($f[$c] !== null && PlanillaEmpleadosReglas::esFecha($f[$c]) && $f[$c] > now()->toDateString()) {
                    $e[] = "{$t} no puede ser futura.";
                }
            }
        }
        if (in_array($hoja, PlanillaEmpleadosReglas::HOJAS_CON_PERIODO, true) && $f['hasta'] !== null && $f['desde'] !== null && $f['hasta'] < $f['desde']) {
            $e[] = '"Hasta" es anterior a "Desde".';
        }
        if ($hoja === 'contratos' && $f['sistema_pension'] !== null && !DB::table('plan_sp_desc')->where('codigo', $f['sistema_pension'])->exists()) {
            $e[] = "Sistema de pensión \"{$f['sistema_pension']}\" no existe: cárgalo en el Excel de recursos (hoja Descuentos SP).";
        }
        if ($hoja === 'derecho_habientes' && $f['tipo'] === 'conyuge' && $f['rol'] !== 'conyuge') {
            $avisos[] = 'Parentesco cónyuge: "El empleado es" debería ser conyuge.';
        }
        return $e;
    }

    // ------------------------------------------------------------------ guardado

    private function empleadoId(string $dni): ?int
    {
        return DB::table('plan_empleados')->where('documento', $dni)->value('id');
    }

    protected function guardarGrupos(array $d): void
    {
        $this->upsert('plan_grupos', ['codigo' => $d['codigo']], ['descripcion' => $d['descripcion'], 'color' => $d['color']]);
    }

    protected function guardarCargos(array $d): void
    {
        $cargo = PlanCargo::withTrashed()->where('slug', PlanCargo::normalizar($d['nombre']))->first() ?? new PlanCargo();
        $cargo->fill(['nombre' => $d['nombre'], 'cupo_maximo' => $d['cupo_maximo'], 'activo' => $d['activo'] === 'SI']);
        $cargo->save();
        if ($cargo->trashed() && $d['activo'] === 'SI') {
            $cargo->restore();
        }
    }

    /** Con el servicio de planilla: valida y crea / actualiza también la persona del empleado. */
    protected function guardarEmpleados(array $d): void
    {
        $id = DB::table('plan_empleados')->where('documento', $d['dni'])->value('id');
        if ($id) {
            // El servicio no ve a los dados de baja: se reactiva y abajo queda según la columna Activo
            DB::table('plan_empleados')->where('id', $id)->update(['deleted_at' => null]);
        }
        $empleado = app(PlanillaEmpleadoServicio::class)->guardar([
            'documento' => $d['dni'], 'nombres' => $d['nombres'], 'apellido_paterno' => $d['apellido_paterno'], 'apellido_materno' => $d['apellido_materno'],
            'fecha_nacimiento' => $d['fecha_nacimiento'], 'genero' => $d['genero'], 'email' => $d['email'], 'numero' => $d['telefono'],
            'direccion' => $d['direccion'], 'fecha_ingreso' => $d['fecha_ingreso'], 'orden' => $d['orden'], 'comentarios' => $d['comentarios'],
        ], $id);
        $baja = DB::table('plan_empleados')->where('id', $empleado->id)->value('deleted_at');
        if ($d['activo'] === 'SI' && $baja) {
            DB::table('plan_empleados')->where('id', $empleado->id)->update(['deleted_at' => null]);
        } elseif ($d['activo'] === 'NO' && !$baja) {
            DB::table('plan_empleados')->where('id', $empleado->id)->update(['deleted_at' => now()]);
        }
    }

    protected function guardarContratos(array $d): void
    {
        $empleadoId = $this->empleadoId($d['dni']);
        if (!$empleadoId) {
            return;
        }
        $this->upsert('plan_contratos', ['plan_empleado_id' => $empleadoId, 'fecha_inicio' => $d['desde']], [
            'fecha_fin' => $d['hasta'], 'tipo_contrato' => $d['tipo_contrato'], 'tipo_planilla' => $d['tipo_planilla'], 'tipo_ingreso' => $d['tipo_ingreso'],
            'grupo_codigo' => $d['grupo'], 'estado' => $d['estado'], 'remuneracion_basica' => $d['remuneracion_basica'], 'bonificacion' => $d['bonificacion'],
            'compensacion_vacacional' => $d['compensacion_vacacional'], 'plan_sp_codigo' => $d['sistema_pension'], 'esta_jubilado' => $d['esta_jubilado'] === 'SI',
            'suspension_cuarta' => $d['suspension_cuarta'] === 'SI', 'beneficios_mensuales' => $d['beneficios_mensuales'] === 'SI',
            'modalidad_pago' => $d['modalidad_pago'], 'metodo_pago' => $d['metodo_pago'], 'banco' => $d['banco'], 'tipo_cuenta' => $d['tipo_cuenta'],
            'moneda_cuenta' => $d['moneda_cuenta'], 'numero_cuenta' => $d['numero_cuenta'], 'banco_secundario' => $d['banco_secundario'],
            'tipo_cuenta_secundaria' => $d['tipo_cuenta_secundaria'], 'moneda_cuenta_secundaria' => $d['moneda_cuenta_secundaria'],
            'numero_cuenta_secundaria' => $d['numero_cuenta_secundaria'], 'fecha_fin_prueba' => $d['fecha_fin_prueba'],
            'motivo_cese_sunat' => $d['motivo_cese_sunat'], 'comentario_cese' => $d['comentario_cese'],
        ]);
    }

    protected function guardarSueldos(array $d): void
    {
        $empleadoId = $this->empleadoId($d['dni']);
        if ($empleadoId) {
            $this->upsert('plan_sueldos', ['plan_empleado_id' => $empleadoId, 'fecha_inicio' => $d['desde']], ['fecha_fin' => $d['hasta'], 'sueldo' => (float) $d['sueldo']]);
        }
    }

    protected function guardarCargosEmpleado(array $d): void
    {
        $empleadoId = $this->empleadoId($d['dni']);
        $cargoId = DB::table('plan_cargos')->where('slug', PlanCargo::normalizar($d['cargo']))->value('id');
        if (!$empleadoId || !$cargoId) {
            return;
        }
        $actual = DB::table('plan_empleado_cargos')->where('plan_empleado_id', $empleadoId)->whereDate('fecha_inicio', $d['desde'])->first();
        $valores = ['plan_cargo_id' => $cargoId, 'grupo_codigo' => $d['grupo'], 'fecha_fin' => $d['hasta'], 'motivo_cambio' => $d['motivo'], 'updated_at' => now()];
        $actual ? DB::table('plan_empleado_cargos')->where('id', $actual->id)->update($valores)
            : DB::table('plan_empleado_cargos')->insert($valores + ['plan_empleado_id' => $empleadoId, 'fecha_inicio' => $d['desde'], 'created_at' => now()]);
    }

    /** El familiar (por documento) y su vínculo con el empleado. */
    protected function guardarDerechoHabientes(array $d): void
    {
        $empleadoId = $this->empleadoId($d['dni']);
        if (!$empleadoId) {
            return;
        }
        $this->upsert('derecho_habientes', ['documento' => $d['documento']], [
            'nombres' => $d['nombres'], 'fecha_nacimiento' => $d['fecha_nacimiento'], 'tipo_documento' => $d['tipo_documento'], 'tipo' => $d['tipo'],
            'discapacidad_severa' => $d['discapacidad_severa'] === 'SI', 'percibe_pension_no_contributiva' => $d['pension_no_contributiva'] === 'SI',
            'fecha_inicio_estudios' => $d['inicio_estudios'], 'fecha_fin_estudios' => $d['fin_estudios'], 'deleted_at' => null,
        ]);
        $habienteId = DB::table('derecho_habientes')->where('documento', $d['documento'])->value('id');
        $vinculo = DB::table('empleado_derecho_habientes')->where('derecho_habiente_id', $habienteId)->where('empleado_id', $empleadoId)->first();
        $valores = ['rol' => $d['rol'], 'activo' => $d['activo'] === 'SI', 'updated_at' => now()];
        $vinculo ? DB::table('empleado_derecho_habientes')->where('id', $vinculo->id)->update($valores)
            : DB::table('empleado_derecho_habientes')->insert($valores + ['empleado_id' => $empleadoId, 'derecho_habiente_id' => $habienteId, 'created_at' => now()]);
    }
}
