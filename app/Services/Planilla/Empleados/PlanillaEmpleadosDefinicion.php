<?php

namespace App\Services\Planilla\Empleados;

use App\Constants\Permisos;
use App\Models\PlanCargo;
use App\Models\PlanContrato;
use App\Models\PlanContratoAsignacion;
use App\Models\PlanContratoRemuneracion;
use App\Models\PlanEmpleado;
use App\Models\PlanEmpleadoPago;
use App\Models\PlanEmpleadoPension;
use App\Services\Planilla\Laboral\PlanillaLaboralConsulta;
use App\Services\Planilla\Laboral\PlanillaLaboralProceso;
use App\Services\Planilla\Laboral\PlanillaLaboralReglas;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelDefinicion;
use App\Services\Sistema\Vigencia\SistemaVigenciaReglas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Excel de empleados sobre el motor común. Lee del sistema y guarda SIEMPRE a través de la capa laboral
 * (PlanillaLaboralProceso / PlanillaLaboralReglas): así el Excel y los formularios validan igual.
 * Se guarda completo o nada (bloquearConErrores): guardar a medias dejaría contratos sin sueldo o grupo.
 */
class PlanillaEmpleadosDefinicion extends SistemaLibroExcelDefinicion
{
    /** @var string[] avisos de la lectura (vigencia) */
    private array $avisos = [];

    /** @var array<string, array>|null contratos leídos del Excel (ya con la vigencia), por DNI */
    private ?array $contratosLeidos = null;

    public function __construct(private PlanillaLaboralProceso $proceso)
    {
    }

    public function reglas(): string
    {
        return PlanillaEmpleadosReglas::class;
    }

    public function permiso(): string
    {
        return Permisos::PERSONAL_CONTRATOS;
    }

    /** Carga inicial: sin datos principales, se acepta un Excel descargado antes o de otra base. */
    public function sistemaVacio(): bool
    {
        return !\Illuminate\Support\Facades\DB::table('plan_empleados')->exists();
    }

    public function permisoHoja(string $hoja): ?string
    {
        return match ($hoja) {
            'grupos' => Permisos::PERSONAL_OPCIONES,
            'cargos', 'asignaciones' => Permisos::PERSONAL_CARGOS,
            'empleados' => Permisos::PERSONAL_EDITAR,
            'contratos' => Permisos::PERSONAL_CONTRATOS,
            'remuneraciones' => Permisos::PERSONAL_REMUNERACIONES,
            'pensiones' => Permisos::PERSONAL_PENSIONES,
            'pagos' => Permisos::PERSONAL_PAGOS,
            'derecho_habientes' => Permisos::PERSONAL_FAMILIARES,
            default => null,
        };
    }

    public function bloquearConErrores(): bool
    {
        return true;
    }

    public function leeme(): array
    {
        $vigencia = SistemaVigenciaReglas::desde();
        return array_filter([
            'El empleado se reconoce por su DNI (8 dígitos) en todas las hojas.',
            'Contratos: un periodo por fila (Inicio / Fin; Fin vacío = vigente). Puede haber días sin contrato entre uno y otro, pero dos contratos del mismo empleado no se pueden cruzar.',
            'Asignaciones (cargo, grupo) y Remuneraciones (sueldo, básica, bonificación…): solo "Desde". Valen hasta la siguiente fila del mismo empleado o hasta el fin del contrato. Para un cambio, agrega una fila con la fecha; no hay que cerrar la anterior.',
            'Cada contrato necesita una asignación y una remuneración con "Desde" igual a su inicio.',
            'Pensiones y Cuentas de pago: solo "Desde"; siguen al empleado aunque cambie de contrato.',
            'Cada fila es completa: copia la anterior y cambia solo lo necesario (una celda vacía queda vacía).',
            'Anular = SI quita esa fila del sistema (por ejemplo, si te equivocaste de fecha de inicio).',
            'Se guarda todo o nada: si una fila tiene errores, corrígela y vuelve a subir.',
            $vigencia ? 'Vigencia del sistema: ' . Carbon::parse($vigencia)->format('d/m/Y') . '. Al subir, lo que terminó antes no se lee y lo vigente empieza en esa fecha.' : null,
            'Sistema de pensión: código de la hoja "Descuentos SP" del Excel de recursos (SNP, HAB F, PRI M…).',
        ]);
    }

    // ------------------------------------------------------------------ filas del sistema

    public function filasSistema(): array
    {
        $sn = fn($v) => PlanillaEmpleadosReglas::sn($v);
        // Fechas imposibles (0001-01-01…) se exportan vacías: Excel no las representa
        $fecha = fn($v) => $v && substr((string) $v, 0, 4) >= '1900' ? substr((string) $v, 0, 10) : null;
        $empleados = PlanEmpleado::withTrashed()->with(['persona', 'usuario'])->alfabetico()->get();
        $dni = $empleados->mapWithKeys(fn($e) => [$e->id => $e->documento]);
        // Contratos vivos y el DNI de su empleado
        $contratos = DB::table('plan_contratos')->whereNull('deleted_at')->whereIn('plan_empleado_id', $dni->keys())
            ->orderBy('plan_empleado_id')->orderBy('fecha_inicio')->get();
        $dniContrato = $contratos->mapWithKeys(fn($c) => [$c->id => $dni[$c->plan_empleado_id]]);
        $cargos = DB::table('plan_cargos')->pluck('nombre', 'id');
        $particulas = fn(string $tabla, string $dueno, $ids, $mapa) => DB::table($tabla)->whereIn($dueno, $ids)->orderBy($dueno)->orderBy('desde')->get()
            ->map(fn($p) => ['_dni' => $mapa[$p->{$dueno}]] + (array) $p);

        return [
            'grupos' => DB::table('plan_grupos')->orderBy('codigo')->get()->map(fn($g) => ['codigo' => $g->codigo, 'descripcion' => $g->descripcion, 'color' => $g->color])->all(),
            'cargos' => DB::table('plan_cargos')->orderBy('nombre')->get()->map(fn($c) => [
                'nombre' => $c->nombre, 'cupo_maximo' => $c->cupo_maximo, 'activo' => $sn($c->activo && !$c->deleted_at),
            ])->all(),
            'empleados' => $empleados->map(fn($e) => [
                'dni' => $e->documento, 'apellido_paterno' => $e->apellido_paterno, 'apellido_materno' => $e->apellido_materno, 'nombres' => $e->nombres,
                'fecha_nacimiento' => $fecha($e->fecha_nacimiento), 'genero' => $e->genero, 'email' => $e->email, 'telefono' => $e->numero,
                'direccion' => $e->direccion, 'orden' => $e->orden, 'comentarios' => $e->comentarios, 'activo' => $sn(!$e->deleted_at),
                'usuario' => $e->usuario?->email,
            ])->all(),
            'contratos' => $contratos->map(fn($c) => [
                'dni' => $dni[$c->plan_empleado_id], 'inicio' => $fecha($c->fecha_inicio), 'fin' => $fecha($c->fecha_fin),
                'tipo_contrato' => $c->tipo_contrato, 'tipo_planilla' => $c->tipo_planilla, 'tipo_ingreso' => $c->tipo_ingreso,
                'fin_prueba' => $fecha($c->fecha_fin_prueba), 'motivo_cese_sunat' => $c->motivo_cese_sunat, 'comentario_cese' => $c->comentario_cese,
                'anular' => 'NO',
            ])->all(),
            'asignaciones' => $particulas('plan_contrato_asignaciones', 'plan_contrato_id', $dniContrato->keys(), $dniContrato)->map(fn($p) => [
                'dni' => $p['_dni'], 'desde' => $fecha($p['desde']), 'cargo' => $cargos[$p['plan_cargo_id']] ?? null, 'grupo' => $p['grupo_codigo'],
                'motivo' => $p['motivo'], 'anular' => 'NO',
            ])->all(),
            'remuneraciones' => $particulas('plan_contrato_remuneraciones', 'plan_contrato_id', $dniContrato->keys(), $dniContrato)->map(fn($p) => [
                'dni' => $p['_dni'], 'desde' => $fecha($p['desde']), 'sueldo' => $p['sueldo'], 'remuneracion_basica' => $p['remuneracion_basica'],
                'bonificacion' => $p['bonificacion'], 'compensacion_vacacional' => $p['compensacion_vacacional'], 'modalidad_pago' => $p['modalidad_pago'],
                'beneficios_mensuales' => $sn($p['beneficios_mensuales']), 'motivo' => $p['motivo'], 'anular' => 'NO',
            ])->all(),
            'pensiones' => $particulas('plan_empleado_pensiones', 'plan_empleado_id', $dni->keys(), $dni)->map(fn($p) => [
                'dni' => $p['_dni'], 'desde' => $fecha($p['desde']), 'sistema_pension' => $p['plan_sp_codigo'], 'esta_jubilado' => $sn($p['esta_jubilado']),
                'suspension_cuarta' => $sn($p['suspension_cuarta']), 'motivo' => $p['motivo'], 'anular' => 'NO',
            ])->all(),
            'pagos' => $particulas('plan_empleado_pagos', 'plan_empleado_id', $dni->keys(), $dni)->map(fn($p) => [
                'dni' => $p['_dni'], 'desde' => $fecha($p['desde']), 'metodo_pago' => $p['metodo_pago'], 'banco' => $p['banco'],
                'tipo_cuenta' => $p['tipo_cuenta'], 'moneda_cuenta' => $p['moneda_cuenta'], 'numero_cuenta' => $p['numero_cuenta'],
                'banco_secundario' => $p['banco_secundario'], 'tipo_cuenta_secundaria' => $p['tipo_cuenta_secundaria'],
                'moneda_cuenta_secundaria' => $p['moneda_cuenta_secundaria'], 'numero_cuenta_secundaria' => $p['numero_cuenta_secundaria'],
                'motivo' => $p['motivo'], 'anular' => 'NO',
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

    // ------------------------------------------------------------------ lectura (vigencia)

    /**
     * Vigencia del sistema (empresa nueva): los contratos que terminaron antes no se leen y los vigentes empiezan en la
     * fecha de vigencia. De cada partícula anterior a la vigencia solo queda la última, movida a esa fecha (y solo si
     * el empleado sigue contratado). Sin vigencia (dominio antiguo) no cambia nada.
     */
    public function prepararHoja(string $hoja, array $filas): array
    {
        $vigencia = SistemaVigenciaReglas::desde();
        if (!$vigencia) {
            return $filas;
        }
        $titulo = PlanillaEmpleadosReglas::HOJAS[$hoja][0] ?? $hoja;
        $fechaTexto = Carbon::parse($vigencia)->format('d/m/Y');
        $esFecha = fn($v) => $v !== null && PlanillaEmpleadosReglas::esFecha($v);

        if ($hoja === 'contratos') {
            $omitidos = $ajustados = 0;
            $r = [];
            foreach ($filas as $f) {
                if ($esFecha($f['fin']) && $f['fin'] < $vigencia) {
                    $omitidos++;
                    continue;
                }
                if ($esFecha($f['inicio']) && $f['inicio'] < $vigencia) {
                    $f['inicio'] = $vigencia;
                    $ajustados++;
                }
                $r[] = $f;
            }
            $this->contratosLeidos = [];
            foreach ($r as $f) {
                $this->contratosLeidos[(string) $f['dni']][] = ['inicio' => $f['inicio'], 'fin' => $f['fin']];
            }
            $omitidos && $this->avisos[] = "{$titulo}: {$omitidos} terminaron antes de la vigencia ({$fechaTexto}) y no se leen.";
            $ajustados && $this->avisos[] = "{$titulo}: {$ajustados} vigentes empiezan el {$fechaTexto} (vigencia del sistema).";
            return $r;
        }

        $esParticula = isset(PlanillaEmpleadosReglas::PARTICULAS_CONTRATO[$hoja]) || isset(PlanillaEmpleadosReglas::PARTICULAS_EMPLEADO[$hoja]);
        if (!$esParticula) {
            return $filas;
        }
        // Por DNI: las de antes de la vigencia se reducen a la última, que pasa a empezar en la vigencia
        $antes = [];
        $r = [];
        foreach ($filas as $f) {
            if ($esFecha($f['desde']) && $f['desde'] < $vigencia) {
                $k = (string) $f['dni'];
                if (!isset($antes[$k]) || $antes[$k]['desde'] <= $f['desde']) {
                    $antes[$k] = $f;
                }
                continue;
            }
            $r[] = $f;
        }
        $movidas = 0;
        $descartadas = count(array_filter($filas, fn($f) => $esFecha($f['desde']) && $f['desde'] < $vigencia)) - count($antes);
        foreach ($antes as $k => $f) {
            $k = (string) $k; // las claves numéricas (DNI) vuelven como int
            $yaTiene = collect($r)->contains(fn($x) => (string) $x['dni'] === $k && $x['desde'] === $vigencia);
            // Del contrato: solo si un contrato cubre la vigencia. Del empleado (pensión, pago): si sigue contratado desde
            // la vigencia (aunque su contrato empiece después), porque su pensión lo acompaña
            $esDelEmpleado = isset(PlanillaEmpleadosReglas::PARTICULAS_EMPLEADO[$hoja]);
            if ($yaTiene || !$this->contratadoEn($k, $vigencia, $esDelEmpleado)) {
                $descartadas++;
                continue;
            }
            $f['desde'] = $vigencia;
            $r[] = $f;
            $movidas++;
        }
        $descartadas && $this->avisos[] = "{$titulo}: {$descartadas} anteriores a la vigencia ({$fechaTexto}) no se leen (historial de la empresa anterior).";
        $movidas && $this->avisos[] = "{$titulo}: {$movidas} vigentes empiezan el {$fechaTexto}.";
        return $r;
    }

    /**
     * ¿El DNI tiene un contrato que cubre la fecha (o, con $desdeFecha, alguno vigente desde esa fecha en adelante)?
     * Usa los contratos del Excel leído o, si no vino la hoja, los del sistema.
     */
    private function contratadoEn(string $dni, string $fecha, bool $desdeFecha = false): bool
    {
        $lista = $this->contratosLeidos !== null ? ($this->contratosLeidos[$dni] ?? [])
            : DB::table('plan_contratos as c')->join('plan_empleados as e', 'e.id', '=', 'c.plan_empleado_id')->join('personas as p', 'p.id', '=', 'e.persona_id')
                ->whereNull('c.deleted_at')->where('p.numero_documento', $dni)->get(['c.fecha_inicio as inicio', 'c.fecha_fin as fin'])->map(fn($c) => (array) $c)->all();
        foreach ($lista as $c) {
            $sigue = $c['fin'] === null || $c['fin'] >= $fecha;
            if ($sigue && ($desdeFecha || $c['inicio'] <= $fecha)) {
                return true;
            }
        }
        return false;
    }

    public function avisosLectura(): array
    {
        $a = $this->avisos;
        $this->avisos = [];
        $this->contratosLeidos = null;
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

    // ------------------------------------------------------------------ validaciones por fila

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
            if ($f['fecha_nacimiento'] !== null && PlanillaEmpleadosReglas::esFecha($f['fecha_nacimiento']) && $f['fecha_nacimiento'] > now()->toDateString()) {
                $e[] = 'La fecha de nacimiento no puede ser futura.';
            }
        }
        if ($hoja === 'pensiones' && $f['sistema_pension'] !== null && !DB::table('plan_sp_desc')->where('codigo', $f['sistema_pension'])->exists()) {
            $e[] = "Sistema de pensión \"{$f['sistema_pension']}\" no existe: cárgalo en el Excel de recursos (hoja Descuentos SP).";
        }
        if ($hoja === 'remuneraciones') {
            foreach (['sueldo', 'remuneracion_basica', 'bonificacion', 'compensacion_vacacional'] as $c) {
                if (is_numeric($f[$c]) && (float) $f[$c] < 0) {
                    $e[] = PlanillaEmpleadosReglas::HOJAS['remuneraciones'][2][$c]['t'] . ' no puede ser negativa.';
                }
            }
        }
        if ($hoja === 'derecho_habientes' && $f['tipo'] === 'conyuge' && $f['rol'] !== 'conyuge') {
            $avisos[] = 'Parentesco cónyuge: "El empleado es" debería ser conyuge.';
        }
        return $e;
    }

    // ------------------------------------------------------------------ validaciones entre filas y hojas

    /**
     * Junta lo del sistema con lo del Excel (lo del Excel reemplaza por su clave; Anular = SI lo quita) y valida con la
     * capa laboral: contratos que se cruzan, partículas fuera de contrato y contratos sin asignación o remuneración
     * desde su inicio. Los errores se marcan en las filas del Excel.
     */
    public function validarLibro(array $hojas, array $sistema): array
    {
        $contratos = $this->combinar('contratos', ['dni', 'inicio'], $hojas, $sistema);

        // 1. Contratos del mismo empleado que se cruzan
        foreach ($contratos as $dni => $lista) {
            $periodos = array_map(fn($c) => ['ref' => $c['ref'], 'inicio' => $c['inicio'], 'fin' => $c['fin'], 'fin_prueba' => $c['fin_prueba']], $lista);
            foreach (PlanillaLaboralReglas::erroresContratos($periodos) as $ref => $errores) {
                foreach ($errores as $msg) {
                    if (str_contains($msg, 'Se cruza') && $this->hayDelSistema($periodos, $msg)) {
                        $msg .= ' Si cambiaste la fecha de inicio de un contrato, el anterior sigue en el sistema: agrégalo en una fila con Anular = SI.';
                    }
                    $this->marcar($hojas, $ref, $msg);
                }
            }
        }

        // 2. Partículas del contrato dentro de algún contrato del empleado; 3. contratos sin partícula en su inicio
        $periodosPorDni = array_map(fn($lista) => array_map(fn($c) => ['ref' => $c['ref'], 'inicio' => $c['inicio'], 'fin' => $c['fin']], $lista), $contratos);
        foreach (PlanillaEmpleadosReglas::PARTICULAS_CONTRATO as $hoja => $titulo) {
            $particulas = $this->combinar($hoja, ['dni', 'desde'], $hojas, $sistema);
            foreach ($particulas as $dni => $lista) {
                $errores = PlanillaLaboralReglas::erroresParticulasDeContrato(array_map(fn($p) => ['ref' => $p['ref'], 'desde' => $p['desde']], $lista),
                    $periodosPorDni[$dni] ?? [], $titulo);
                foreach ($errores as $ref => $msgs) {
                    foreach ($msgs as $msg) {
                        $this->marcar($hojas, $ref, $msg);
                    }
                }
            }
            $tituloHoja = PlanillaEmpleadosReglas::HOJAS[$hoja][0];
            foreach ($periodosPorDni as $dni => $periodos) {
                // Solo se exige en los contratos que vienen nuevos o cambian en este Excel
                $delExcel = array_filter($periodos, fn($c) => str_starts_with($c['ref'], 'contratos:') && $this->filaPorRef($hojas, $c['ref'])['estado'] !== 'igual');
                $desdes = array_map(fn($p) => ['ref' => $p['ref'], 'desde' => $p['desde']], $particulas[$dni] ?? []);
                $nombre = mb_strtolower(preg_replace('/^(La|El) /', '', $titulo)); // "asignación", "remuneración"
                foreach (PlanillaLaboralReglas::contratosSinInicio($delExcel, $desdes, $nombre) as $ref => $msg) {
                    $this->marcar($hojas, $ref, "{$msg} Agrégala en la hoja {$tituloHoja}.");
                }
            }
        }

        // 4. Avisos: Anular en filas que no existen; contratos de planilla sin sistema de pensión
        foreach ($hojas as $hoja => &$h) {
            foreach ($h['filas'] as &$f) {
                if (($f['datos']['anular'] ?? 'NO') === 'SI' && $f['estado'] === 'nuevo') {
                    $f['avisos'][] = 'Anular = SI en una fila que no está en el sistema: se ignora.';
                }
            }
            unset($f);
        }
        unset($h);
        $pensiones = $this->combinar('pensiones', ['dni', 'desde'], $hojas, $sistema);
        foreach ($hojas['contratos']['filas'] ?? [] as $i => $f) {
            $d = $f['datos'];
            if ($f['estado'] === 'nuevo' && $d['tipo_ingreso'] === 'planilla' && $d['tipo_planilla'] !== 'externa' && empty($pensiones[(string) $d['dni']])) {
                $hojas['contratos']['filas'][$i]['avisos'][] = 'El empleado no tiene sistema de pensión (hoja Pensiones): la planilla no podrá calcular su descuento.';
            }
        }
        return $hojas;
    }

    /**
     * Filas del sistema + del Excel por DNI. Cada una con 'ref' ("hoja:i" si viene del Excel, "sistema" si no) y sus
     * datos. Las del Excel con errores propios no entran (ya están marcadas); Anular = SI quita la fila.
     * @return array<string, array<int, array>>
     */
    private function combinar(string $hoja, array $clave, array $hojas, array $sistema): array
    {
        $porClave = [];
        $k = fn($f) => implode('|', array_map(fn($c) => mb_strtolower((string) $f[$c]), $clave));
        foreach ($sistema[$hoja] ?? [] as $f) {
            $porClave[$k($f)] = ['ref' => 'sistema'] + $f;
        }
        foreach ($hojas[$hoja]['filas'] ?? [] as $i => $f) {
            if ($f['estado'] === 'error') {
                continue;
            }
            if (($f['datos']['anular'] ?? 'NO') === 'SI') {
                unset($porClave[$k($f['datos'])]);
                continue;
            }
            $porClave[$k($f['datos'])] = ['ref' => "{$hoja}:{$i}"] + $f['datos'];
        }
        $r = [];
        foreach ($porClave as $f) {
            $r[(string) $f['dni']][] = $f;
        }
        return $r;
    }

    private function hayDelSistema(array $periodos, string $msg): bool
    {
        foreach ($periodos as $p) {
            if ($p['ref'] === 'sistema' && str_contains($msg, PlanillaLaboralReglas::periodo($p))) {
                return true;
            }
        }
        return false;
    }

    private function filaPorRef(array $hojas, string $ref): array
    {
        [$hoja, $i] = explode(':', $ref);
        return $hojas[$hoja]['filas'][(int) $i];
    }

    /** Agrega el error a la fila del Excel (las del sistema no se marcan: no están en el archivo). */
    private function marcar(array &$hojas, string $ref, string $msg): void
    {
        if (!str_contains($ref, ':')) {
            return;
        }
        [$hoja, $i] = explode(':', $ref);
        $hojas[$hoja]['filas'][(int) $i]['errores'][] = $msg;
    }

    // ------------------------------------------------------------------ guardado (siempre por la capa laboral)

    /** Contratos: primero los anulados, luego los que cambian y al final los nuevos (así un cierre va antes que el contrato siguiente). */
    public function ordenarParaGuardar(string $hoja, array $filas): array
    {
        if ($hoja !== 'contratos') {
            return $filas;
        }
        usort($filas, fn($a, $b) => [($a['datos']['anular'] === 'SI' ? 0 : ($a['estado'] === 'cambia' ? 1 : 2)), $a['datos']['inicio']]
            <=> [($b['datos']['anular'] === 'SI' ? 0 : ($b['estado'] === 'cambia' ? 1 : 2)), $b['datos']['inicio']]);
        return $filas;
    }

    private function empleado(string $dni): ?PlanEmpleado
    {
        return PlanEmpleado::withTrashed()->porDocumento($dni)->first();
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

    protected function guardarEmpleados(array $d): void
    {
        $empleado = $this->proceso->guardarEmpleado([
            'documento' => $d['dni'], 'nombres' => $d['nombres'], 'apellido_paterno' => $d['apellido_paterno'], 'apellido_materno' => $d['apellido_materno'],
            'fecha_nacimiento' => $d['fecha_nacimiento'], 'genero' => $d['genero'], 'email' => $d['email'], 'numero' => $d['telefono'],
            'direccion' => $d['direccion'], 'orden' => $d['orden'], 'comentarios' => $d['comentarios'],
        ], $this->empleado($d['dni'])?->id);
        if ($d['activo'] === 'SI' && $empleado->trashed()) {
            $empleado->restore();
        } elseif ($d['activo'] === 'NO' && !$empleado->trashed()) {
            $empleado->delete();
        }
    }

    protected function guardarContratos(array $d): void
    {
        $empleado = $this->empleado($d['dni']);
        $contrato = $empleado?->contratos()->whereDate('fecha_inicio', $d['inicio'])->first();
        if (!$empleado || $d['anular'] === 'SI') {
            $contrato && $this->proceso->eliminarContrato($contrato);
            return;
        }
        $this->proceso->guardarContrato($empleado, [
            'tipo_contrato' => $d['tipo_contrato'], 'tipo_planilla' => $d['tipo_planilla'], 'tipo_ingreso' => $d['tipo_ingreso'],
            'fecha_inicio' => $d['inicio'], 'fecha_fin' => $d['fin'], 'fecha_fin_prueba' => $d['fin_prueba'],
            'motivo_cese_sunat' => $d['motivo_cese_sunat'], 'comentario_cese' => $d['comentario_cese'],
        ], $contrato);
    }

    /** Contrato del empleado que cubre la fecha (el "Desde" de la partícula decide a qué contrato pertenece). */
    private function contratoDe(string $dni, string $desde): ?PlanContrato
    {
        $empleado = $this->empleado($dni);
        return $empleado ? PlanillaLaboralConsulta::contratoEn($empleado->setRelation('contratos', $empleado->contratos()->get()), $desde) : null;
    }

    protected function guardarAsignaciones(array $d): void
    {
        $contrato = $this->contratoDe($d['dni'], $d['desde']);
        if ($d['anular'] === 'SI') {
            $contrato && PlanContratoAsignacion::where('plan_contrato_id', $contrato->id)->whereDate('desde', $d['desde'])->delete();
            return;
        }
        $this->proceso->guardarAsignacion($contrato, [
            'desde' => $d['desde'], 'grupo_codigo' => $d['grupo'], 'motivo' => $d['motivo'],
            'plan_cargo_id' => $d['cargo'] ? DB::table('plan_cargos')->where('slug', PlanCargo::normalizar($d['cargo']))->value('id') : null,
        ]);
    }

    protected function guardarRemuneraciones(array $d): void
    {
        $contrato = $this->contratoDe($d['dni'], $d['desde']);
        if ($d['anular'] === 'SI') {
            $contrato && PlanContratoRemuneracion::where('plan_contrato_id', $contrato->id)->whereDate('desde', $d['desde'])->delete();
            return;
        }
        $this->proceso->guardarRemuneracion($contrato, [
            'desde' => $d['desde'], 'sueldo' => $d['sueldo'], 'remuneracion_basica' => $d['remuneracion_basica'], 'bonificacion' => $d['bonificacion'],
            'compensacion_vacacional' => $d['compensacion_vacacional'], 'modalidad_pago' => $d['modalidad_pago'],
            'beneficios_mensuales' => $d['beneficios_mensuales'] === 'SI', 'motivo' => $d['motivo'],
        ]);
    }

    protected function guardarPensiones(array $d): void
    {
        $empleado = $this->empleado($d['dni']);
        if (!$empleado) {
            return;
        }
        if ($d['anular'] === 'SI') {
            PlanEmpleadoPension::where('plan_empleado_id', $empleado->id)->whereDate('desde', $d['desde'])->delete();
            return;
        }
        $this->proceso->guardarPension($empleado, [
            'desde' => $d['desde'], 'plan_sp_codigo' => $d['sistema_pension'], 'esta_jubilado' => $d['esta_jubilado'] === 'SI',
            'suspension_cuarta' => $d['suspension_cuarta'] === 'SI', 'motivo' => $d['motivo'],
        ]);
    }

    protected function guardarPagos(array $d): void
    {
        $empleado = $this->empleado($d['dni']);
        if (!$empleado) {
            return;
        }
        if ($d['anular'] === 'SI') {
            PlanEmpleadoPago::where('plan_empleado_id', $empleado->id)->whereDate('desde', $d['desde'])->delete();
            return;
        }
        $this->proceso->guardarPago($empleado, $d);
    }

    /** El familiar (por documento) y su vínculo con el empleado. */
    protected function guardarDerechoHabientes(array $d): void
    {
        $empleadoId = $this->empleado($d['dni'])?->id;
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
