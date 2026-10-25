<?php

namespace App\Services\Sistema\Recursos;

use App\Services\Sistema\PermisosServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Filas de cada hoja del Excel de recursos tal como están en el sistema (valores crudos: el motor común los normaliza
 * con SistemaLibroExcelReglas::normalizar). Sirven para exportar y para comparar al importar.
 */
class SistemaRecursosExportarConsulta
{
    /** @return array<string, array<int, array<string, mixed>>> hoja => filas */
    public function filas(): array
    {
        $r = [];
        foreach (array_keys(SistemaRecursosReglas::HOJAS) as $hoja) {
            $r[$hoja] = $this->{'hoja' . str_replace('_', '', ucwords($hoja, '_'))}();
        }
        return $r;
    }

    /**
     * Matriz de roles: permisos del árbol (con su módulo) y los permisos de cada rol (sin Super Admin).
     *
     * @return array{permisos: array<int, array{modulo: string, permiso: string}>, roles: array<string, string[]>}
     */
    public function roles(): array
    {
        $permisos = [];
        foreach (config('permisos_tree') as $modulo) {
            foreach (PermisosServicio::aplanarArbol([$modulo]) as $p) {
                $permisos[] = ['modulo' => $modulo['nombre'], 'permiso' => $p];
            }
        }
        $roles = [];
        foreach (DB::table('roles')->where('guard_name', 'web')->where('name', '<>', 'Super Admin')->orderBy('id')->pluck('name', 'id') as $id => $nombre) {
            $roles[$nombre] = DB::table('role_has_permissions as rp')->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('rp.role_id', $id)->orderBy('p.name')->pluck('p.name')->all();
        }
        return ['permisos' => $permisos, 'roles' => $roles];
    }

    private function hojaEmpresa(): array
    {
        return DB::table('empresa')->get()->map(fn($e) => ['ruc' => $e->ruc, 'razon_social' => $e->razon_social, 'establecimiento' => $e->establecimiento])->all();
    }

    private function hojaCampos(): array
    {
        return DB::table('campos')->orderBy('orden')->orderBy('nombre')->get()->map(fn($c) => [
            'nombre' => $c->nombre, 'padre' => $c->campo_parent_nombre, 'grupo' => $c->grupo, 'orden' => $c->orden,
            'etapa' => $c->etapa, 'area' => $c->area, 'alias' => $c->alias,
        ])->all();
    }

    private function hojaManoObra(): array
    {
        return DB::table('mano_obras')->orderBy('codigo')->get()->map(fn($m) => ['codigo' => $m->codigo, 'descripcion' => $m->descripcion])->all();
    }

    private function hojaTiposSuspension(): array
    {
        return DB::table('plan_tipos_suspension')->orderBy('codigo')->get()->map(fn($s) => [
            'codigo' => $s->codigo, 'grupo' => $s->grupo, 'descripcion' => $s->descripcion, 'descripcion_corta' => $s->descripcion_corta,
            'incluye_domingos' => SistemaRecursosReglas::sn($s->incluye_domingos),
        ])->all();
    }

    private function hojaTiposAsistencia(): array
    {
        $suspensiones = DB::table('plan_tipos_suspension')->pluck('codigo', 'id');
        return DB::table('plan_tipo_asistencias')->orderBy('id')->get()->map(fn($a) => [
            'codigo' => $a->codigo, 'descripcion' => $a->descripcion, 'horas_jornal' => $a->horas_jornal, 'color' => $a->color, 'tipo' => $a->tipo,
            'afecta_sueldo' => SistemaRecursosReglas::sn($a->afecta_sueldo), 'porcentaje_remunerado' => $a->porcentaje_remunerado,
            'requiere_documento' => SistemaRecursosReglas::sn($a->requiere_documento), 'acumula_vacaciones' => SistemaRecursosReglas::sn($a->acumula_vacaciones),
            'acumula_asistencia' => SistemaRecursosReglas::sn($a->acumula_asistencia),
            'tipo_suspension' => $suspensiones[$a->plan_tipo_suspension_id] ?? null, 'sin_suspension' => SistemaRecursosReglas::sn($a->sin_suspension ?? false),
            'rol' => $a->rol ?? null, 'criterio_bono' => $a->criterio_bono_asistencia ?? null, 'activo' => SistemaRecursosReglas::sn($a->activo),
        ])->all();
    }

    private function hojaUnidadesProduccion(): array
    {
        return DB::table('produccion_unidades')->orderBy('codigo')->get()->map(fn($u) => [
            'codigo' => $u->codigo, 'nombre' => $u->nombre, 'decimales' => $u->decimales, 'activo' => SistemaRecursosReglas::sn($u->activo),
        ])->all();
    }

    private function hojaLabores(): array
    {
        return DB::table('labores')->orderBy('codigo')->get()->map(fn($l) => [
            'codigo' => $l->codigo, 'nombre' => $l->nombre_labor, 'mano_obra' => $l->codigo_mano_obra, 'tipo_asistencia' => $l->tipo_asistencia_codigo,
            'rol_campania' => $l->rol_campania ?? null, 'vigente_desde' => $l->vigente_desde, 'estandar' => $l->estandar_produccion,
            'registra_produccion' => SistemaRecursosReglas::sn($l->registra_produccion ?? false), 'unidades' => $l->unidades,
            'se_paga_con_jornal' => SistemaRecursosReglas::sn($l->se_paga_con_jornal), 'activa' => SistemaRecursosReglas::sn(!$l->deleted_at),
        ])->all();
    }

    private function hojaTramos(): array
    {
        $filas = [];
        foreach (DB::table('labores')->whereNotNull('tramos_bonificacion')->orderBy('codigo')->get(['codigo', 'tramos_bonificacion']) as $l) {
            foreach (self::tramos($l->tramos_bonificacion) as $i => $t) {
                $filas[] = ['codigo' => $l->codigo, 'hasta' => $t['hasta'] ?? null, 'monto' => $t['monto'] ?? null, 'n' => $i + 1];
            }
        }
        return $filas;
    }

    /** Tramos guardados como JSON (a veces doblemente codificado). @return array<int, array{hasta: mixed, monto: mixed}> */
    public static function tramos(mixed $valor): array
    {
        for ($i = 0; $i < 3 && is_string($valor); $i++) {
            $valor = json_decode($valor, true);
        }
        return is_array($valor) ? array_values(array_filter($valor, 'is_array')) : [];
    }

    private function hojaRiego(): array
    {
        return DB::table('reg_labores')->orderBy('nombre_labor')->get()->map(fn($l) => [
            'nombre' => $l->nombre_labor, 'es_riego' => SistemaRecursosReglas::sn($l->es_riego), 'es_apoyo_riego' => SistemaRecursosReglas::sn($l->es_apoyo_riego),
            'consumo_m3_hora' => $l->consumo_m3_hora,
        ])->all();
    }

    private function hojaNutrientes(): array
    {
        return DB::table('nutrientes')->orderBy('codigo')->get()->map(fn($n) => ['codigo' => $n->codigo, 'nombre' => $n->nombre, 'unidad' => $n->unidad])->all();
    }

    private function hojaConfiguracion(): array
    {
        return DB::table('configuracion')->orderBy('codigo')->get()->map(fn($c) => ['codigo' => $c->codigo, 'valor' => $c->valor, 'descripcion' => $c->descripcion])->all();
    }

    private function hojaConfiguracionFecha(): array
    {
        return DB::table('configuracion_historial')->orderBy('configuracion_codigo')->orderBy('fecha_inicio')->get()->map(fn($c) => [
            'codigo' => $c->configuracion_codigo, 'valor' => $c->valor, 'desde' => $c->fecha_inicio, 'hasta' => $c->fecha_fin,
            'motivo' => $c->motivo,
        ])->all();
    }

    private function hojaSp(): array
    {
        return DB::table('plan_sp_desc')->orderBy('orden')->get()->map(fn($s) => [
            'codigo' => $s->codigo, 'referencia' => $s->referencia, 'orden' => $s->orden, 'descripcion' => $s->descripcion, 'tipo' => $s->tipo,
            'porcentaje' => $s->porcentaje, 'porcentaje_65' => $s->porcentaje_65, 'color' => $s->color,
        ])->all();
    }

    private function hojaSpFecha(): array
    {
        return DB::table('plan_sp_desc_hist')->orderBy('fecha_inicio')->orderBy('descuento_codigo')->get()->map(fn($s) => [
            'codigo' => $s->descuento_codigo, 'desde' => $s->fecha_inicio, 'hasta' => $s->fecha_fin, 'porcentaje' => $s->porcentaje, 'porcentaje_65' => $s->porcentaje_65,
        ])->all();
    }

    private function hojaAfp(): array
    {
        return DB::table('plan_prima_comision_historicos')->orderBy('fecha_inicio')->orderBy('referencia')->get()->map(fn($a) => [
            'afp' => $a->referencia, 'desde' => $a->fecha_inicio, 'comision_flujo' => $a->comision_flujo, 'comision_saldo' => $a->comision_saldo,
            'prima_seguros' => $a->prima_seguros, 'aporte_obligatorio' => $a->aporte_obligatorio, 'remuneracion_maxima' => $a->remuneracion_maxima_asegurable,
            'motivo' => $a->motivo,
        ])->all();
    }

    private function hojaMaquinarias(): array
    {
        $productos = DB::table('productos')->pluck('nombre_comercial', 'id');
        return DB::table('maquinarias')->orderBy('nombre')->get()->map(fn($m) => [
            'nombre' => $m->nombre, 'alias_blanco' => $m->alias_blanco, 'combustible' => $productos[$m->combustible_producto_id] ?? null,
            'usa_distribucion' => SistemaRecursosReglas::sn($m->usa_distribucion ?? true), 'consumo_modo' => $m->consumo_modo ?? null,
            'consumo_estimado' => $m->consumo_estimado ?? null, 'placa' => $m->placa ?? null,
        ])->all();
    }

    private function hojaGrupos(): array
    {
        return DB::table('cuad_grupos')->orderBy('codigo')->get()->map(fn($g) => [
            'codigo' => $g->codigo, 'nombre' => $g->nombre, 'color' => $g->color, 'modalidad_pago' => $g->modalidad_pago,
            'costo_dia' => $g->costo_dia_sugerido, 'activo' => SistemaRecursosReglas::sn(!$g->deleted_at),
        ])->all();
    }

    private function hojaObservaciones(): array
    {
        return DB::table('cochinilla_observaciones')->orderBy('descripcion')->get()->map(fn($o) => [
            'codigo' => $o->codigo, 'descripcion' => $o->descripcion, 'es_cosecha_mama' => SistemaRecursosReglas::sn($o->es_cosecha_mama ?? false),
            'es_vendible' => SistemaRecursosReglas::sn($o->es_vendible ?? true),
        ])->all();
    }

    private function hojaUsuarios(): array
    {
        $roles = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('m.model_type', \App\Models\User::class)->orderBy('r.name')->get(['m.model_id', 'r.name'])->groupBy('model_id');
        return DB::table('users')->orderBy('id')->get()->map(fn($u) => [
            'nombre' => $u->name, 'correo' => mb_strtolower($u->email), 'roles' => ($roles[$u->id] ?? collect())->pluck('name')->implode(', '),
            'activo' => SistemaRecursosReglas::sn(Schema::hasColumn('users', 'estado') ? $u->estado : true), 'clave' => null,
            'clave_inicial' => SistemaRecursosReglas::claveInicial($u->email),
        ])->all();
    }
}
