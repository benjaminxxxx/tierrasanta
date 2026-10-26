<?php

namespace App\Services\Sistema\Recursos;

use App\Constants\Permisos;
use App\Models\User;
use App\Services\Planilla\Parametro\PlanillaParametroProceso;
use App\Services\Reporte\AuditoriaServicio;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelDefinicion;
use App\Services\Sistema\PermisosServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Excel de recursos (motor común SistemaLibroExcel): catálogos que definen cómo trabaja la empresa (empresa, campos,
 * labores, asistencia, parámetros, AFP, maquinarias, grupos de cuadrilla, observaciones, roles y usuarios).
 * Nunca borra: "Activo/Activa = NO" da de baja (labores, grupos) o desactiva (usuarios).
 */
class SistemaRecursosDefinicion extends SistemaLibroExcelDefinicion
{
    public function __construct(private SistemaRecursosExportarConsulta $consulta)
    {
    }

    public function reglas(): string
    {
        return SistemaRecursosReglas::class;
    }

    public function permiso(): string
    {
        return Permisos::SISTEMA_CONFIGURACION;
    }

    /** Carga inicial: sin datos principales, se acepta un Excel descargado antes o de otra base. */
    public function sistemaVacio(): bool
    {
        return !\Illuminate\Support\Facades\DB::table('campos')->exists() && !\Illuminate\Support\Facades\DB::table('labores')->exists();
    }

    public function filasSistema(): array
    {
        return $this->consulta->filas();
    }

    public function leeme(): array
    {
        return [
            'Empresa: una sola fila con los datos de la empresa de este sistema (salen en el kardex en Excel).',
            'Usuarios: "Clave nueva" vacía no cambia la clave de un usuario existente. Un usuario nuevo sin clave recibe la clave de referencia (parte del correo antes de la @ + ' . SistemaRecursosReglas::SUFIJO_CLAVE . ').',
            'Roles y permisos: una X marca el permiso del rol. Para un rol nuevo, agrega una columna con su nombre. Super Admin tiene todos los permisos.',
            'Maquinarias: el combustible se busca por el nombre comercial del producto; si aún no existe, la máquina se guarda sin combustible (avisa).',
        ];
    }

    // ------------------------------------------------------------------ análisis

    /** Tramos: número de orden dentro de su labor (identifica la fila junto con el código). */
    public function prepararHoja(string $hoja, array $filas): array
    {
        if ($hoja !== 'tramos') {
            return $filas;
        }
        $n = [];
        foreach ($filas as &$f) {
            $f['n'] = (string) ($n[$f['codigo']] = ($n[$f['codigo']] ?? 0) + 1);
        }
        return $filas;
    }

    public function prepararFila(string $hoja, array $f): array
    {
        if ($hoja === 'usuarios') {
            $f['correo'] = $f['correo'] !== null ? mb_strtolower($f['correo']) : null;
            $roles = self::lista($f['roles']);
            sort($roles);
            $f['roles'] = $roles ? implode(', ', $roles) : null;
        }
        // Unidades: siempre el código del catálogo (lavadores / lavaderos → LAVADOR, kg → KG)
        if ($hoja === 'unidades_produccion' && $f['codigo'] !== null) {
            $f['codigo'] = \App\Services\Campo\Produccion\CampoProduccionReglas::codigo($f['codigo']);
        }
        if ($hoja === 'labores') {
            $f['unidades'] = \App\Services\Campo\Produccion\CampoProduccionReglas::codigo($f['unidades']);
        }
        return $f;
    }

    public function reglasFila(string $hoja, array $f, array &$avisos, array $contexto): array
    {
        $e = [];
        // Registra producción = SI exige unidad; = NO, la unidad se ignora (misma regla que el formulario de labores)
        if ($hoja === 'labores' && $f['registra_produccion'] === 'SI' && $f['unidades'] === null) {
            $e[] = 'Registra producción = SI: indica la unidad de producción.';
        }
        if ($hoja === 'labores' && $f['registra_produccion'] === 'NO' && $f['unidades'] !== null) {
            $avisos[] = "Registra producción = NO: la unidad \"{$f['unidades']}\" no se guarda.";
        }
        // Parámetros y AFP: valores el día 1 del mes; si el cambio toca planillas generadas se hace en la pantalla (confirma y regenera)
        if (in_array($hoja, ['configuracion_fecha', 'afp'], true) && $f['desde'] !== null) {
            if (substr((string) $f['desde'], 8, 2) !== '01') {
                $e[] = 'Desde debe ser el día 1 de un mes: el valor rige por meses.';
            } elseif ($hoja === 'configuracion_fecha' && $f['codigo'] !== null && $f['valor'] !== null) {
                $error = app(PlanillaParametroProceso::class)->errorSinGuardar(fn() => app(PlanillaParametroProceso::class)
                    ->guardarDesdeExcel($f['codigo'], $f['valor'], $f['desde'], $f['motivo'] ?? null));
                $error && $e[] = $error;
            } elseif ($hoja === 'afp' && $f['afp'] !== null) {
                $error = app(PlanillaParametroProceso::class)->errorSinGuardar(fn() => app(PlanillaParametroProceso::class)
                    ->guardarAfp($f['desde'], [$f['afp'] => $this->datosAfp($f)], $f['motivo'] ?? null));
                $error && $e[] = $error;
            }
        }
        if ($hoja === 'empresa' && $f['fila'] > 2) {
            $e[] = 'La hoja Empresa lleva una sola fila (la empresa de este sistema).';
        }
        if ($hoja === 'empresa' && $f['ruc'] !== null && !preg_match('/^\d{11}$/', $f['ruc'])) {
            $e[] = 'El RUC debe tener 11 dígitos.';
        }
        if ($hoja === 'usuarios' && $f['correo'] !== null && !filter_var($f['correo'], FILTER_VALIDATE_EMAIL)) {
            $e[] = "Correo \"{$f['correo']}\" no es válido.";
        }
        if ($hoja === 'usuarios' && $f['clave'] !== null && mb_strlen($f['clave']) < 8) {
            $e[] = 'La clave nueva debe tener al menos 8 caracteres.';
        }
        if ($hoja === 'usuarios' && $f['roles'] !== null) {
            $validos = array_merge(DB::table('roles')->pluck('name')->all(), ['Super Admin'], $contexto['matriz'] ?? []);
            foreach (self::lista($f['roles']) as $rol) {
                if (!in_array($rol, $validos, true)) {
                    $e[] = "Rol \"{$rol}\" no existe (agrégalo como columna en la hoja " . SistemaRecursosReglas::TITULO_ROLES . ').';
                }
            }
        }
        if ($hoja === 'maquinarias' && $f['combustible'] !== null
            && !DB::table('productos')->whereRaw('LOWER(nombre_comercial) = ?', [mb_strtolower($f['combustible'])])->exists()) {
            $avisos[] = "El producto \"{$f['combustible']}\" aún no existe: se guarda sin combustible.";
        }
        if ($hoja === 'maquinarias' && $f['consumo_modo'] !== null && $f['consumo_estimado'] === null) {
            $e[] = 'Con modo de consumo falta el consumo estimado.';
        }
        if (in_array($hoja, ['configuracion_fecha', 'sp_fecha'], true) && $f['hasta'] !== null && $f['desde'] !== null && $f['hasta'] < $f['desde']) {
            $e[] = '"Hasta" es anterior a "Desde".';
        }
        if ($hoja === 'campos' && $f['padre'] !== null && mb_strtolower($f['padre']) === mb_strtolower((string) $f['nombre'])) {
            $e[] = 'Un campo no puede ser su propio padre.';
        }
        return $e;
    }

    public function cambiosExtra(string $hoja, array $antes, array $f): array
    {
        return $hoja === 'usuarios' && $f['clave'] !== null ? ['Clave nueva'] : [];
    }

    public function avisosNueva(string $hoja, array $f): array
    {
        return $hoja === 'usuarios' && $f['clave'] === null ? ['Clave inicial: ' . SistemaRecursosReglas::claveInicial($f['correo'])] : [];
    }

    // ------------------------------------------------------------------ matriz de roles

    public function matrizSistema(): ?array
    {
        $r = $this->consulta->roles();
        return [
            'fijas' => ['Módulo', 'Permiso'],
            'filas' => array_map(fn($p) => [$p['modulo'], $p['permiso']], $r['permisos']),
            'columnas' => $r['roles'],
        ];
    }

    public function analizarMatriz(array $excel): ?array
    {
        $arbol = PermisosServicio::aplanarArbol(config('permisos_tree'));
        $sistema = $this->consulta->roles()['roles'];
        $filas = [];
        $desconocidos = [];
        foreach ($excel['columnas'] as $rol) {
            if ($rol === 'Super Admin') {
                continue;
            }
            $marcas = array_values(array_unique($excel['marcas'][$rol]));
            $desconocidos = array_merge($desconocidos, array_diff($marcas, $arbol));
            $marcas = array_values(array_intersect($marcas, $arbol));
            $antes = $sistema[$rol] ?? null;
            $agrega = $antes === null ? $marcas : array_values(array_diff($marcas, $antes));
            $quita = $antes === null ? [] : array_values(array_diff(array_intersect($antes, $arbol), $marcas));
            $filas[] = ['nombre' => $rol, 'estado' => $antes === null ? 'nuevo' : (($agrega || $quita) ? 'cambia' : 'igual'),
                'marcas' => $marcas, 'agrega' => $agrega, 'quita' => $quita];
        }
        return [
            'filas' => $filas,
            'avisos' => $desconocidos ? ['Permisos que no existen en el sistema (se ignoran): ' . implode(', ', array_unique($desconocidos))] : [],
            'solo_sistema' => array_values(array_diff(array_keys($sistema), $excel['columnas'])),
            'etiquetas' => ['nombre' => 'Rol', 'marcas' => 'Permisos'],
        ];
    }

    public function guardarMatriz(array $analisis): int
    {
        PermisosServicio::sincronizarPermisosEnBD(config('permisos_tree'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $n = 0;
        foreach ($analisis['filas'] as $f) {
            if ($f['estado'] === 'igual') {
                continue;
            }
            Role::firstOrCreate(['name' => $f['nombre'], 'guard_name' => 'web'])->syncPermissions($f['marcas']);
            AuditoriaServicio::registrar(modelo: 'Excel de recursos: Roles', modeloId: $f['nombre'], accion: $f['estado'] === 'nuevo' ? 'crear' : 'editar',
                despues: ['agrega' => $f['agrega'], 'quita' => $f['quita']], observacion: 'Importación Excel de recursos');
            $n++;
        }
        return $n;
    }

    public function matrizAntesDe(): ?string
    {
        return 'usuarios';
    }

    public function despuesDeGuardar(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ------------------------------------------------------------------ guardado

    /** Tramos: por cada labor del Excel, si su lista cambió se reemplaza entera. */
    public function guardarHoja(string $hoja, array $filasAnalizadas): ?int
    {
        if ($hoja !== 'tramos') {
            return null;
        }
        $n = 0;
        foreach (collect($filasAnalizadas)->groupBy(fn($f) => $f['datos']['codigo']) as $codigo => $grupo) {
            if ($grupo->contains('estado', 'error')) {
                continue;
            }
            $lista = $grupo->map(fn($f) => ['hasta' => (float) $f['datos']['hasta'], 'monto' => (float) $f['datos']['monto']])->values()->all();
            $labor = DB::table('labores')->where('codigo', $codigo)->first(['id', 'tramos_bonificacion']);
            $antes = array_map(fn($t) => ['hasta' => (float) ($t['hasta'] ?? 0), 'monto' => (float) ($t['monto'] ?? 0)],
                SistemaRecursosExportarConsulta::tramos($labor?->tramos_bonificacion));
            if (!$labor || $antes == $lista) {
                continue;
            }
            DB::table('labores')->where('id', $labor->id)->update(['tramos_bonificacion' => json_encode($lista), 'updated_at' => now()]);
            AuditoriaServicio::registrar(modelo: 'Excel de recursos: Tramos de bono', modeloId: (string) $codigo, accion: 'editar',
                antes: ['tramos' => $antes], despues: ['tramos' => $lista], observacion: 'Importación Excel de recursos');
            $n++;
        }
        return $n;
    }

    /** Campos: los padres antes que sus hijos. */
    public function ordenarParaGuardar(string $hoja, array $filas): array
    {
        if ($hoja !== 'campos') {
            return $filas;
        }
        $orden = [];
        $pendientes = $filas;
        while ($pendientes) {
            $nombres = array_map(fn($f) => mb_strtolower($f['datos']['nombre']), $pendientes);
            $listos = array_filter($pendientes, fn($f) => !$f['datos']['padre'] || !in_array(mb_strtolower($f['datos']['padre']), $nombres, true));
            if (!$listos) { // ciclo: se guardan como vengan
                return array_merge($orden, $pendientes);
            }
            $orden = array_merge($orden, $listos);
            $pendientes = array_diff_key($pendientes, $listos);
        }
        return $orden;
    }

    /** Empresa: un solo registro; con otro RUC se reemplaza (no se agrega una segunda empresa). */
    protected function guardarEmpresa(array $d): void
    {
        DB::table('empresa')->where('ruc', '<>', $d['ruc'])->delete();
        DB::table('empresa')->updateOrInsert(['ruc' => $d['ruc']], ['razon_social' => $d['razon_social'], 'establecimiento' => $d['establecimiento']]);
    }

    protected function guardarCampos(array $d): void
    {
        $existe = DB::table('campos')->where('nombre', $d['nombre'])->exists();
        $valores = ['campo_parent_nombre' => $d['padre'], 'grupo' => $d['grupo'], 'orden' => $d['orden'], 'etapa' => $d['etapa'],
            'area' => $d['area'], 'alias' => $d['alias'], 'bloqueado' => ($d['bloqueado'] ?? 'NO') === 'SI', 'updated_at' => now()];
        $existe ? DB::table('campos')->where('nombre', $d['nombre'])->update($valores)
            : DB::table('campos')->insert($valores + ['nombre' => $d['nombre'], 'created_at' => now()]);
    }

    protected function guardarManoObra(array $d): void
    {
        $this->upsert('mano_obras', ['codigo' => $d['codigo']], ['descripcion' => $d['descripcion']]);
    }

    protected function guardarTiposSuspension(array $d): void
    {
        $this->upsert('plan_tipos_suspension', ['codigo' => $d['codigo']], [
            'grupo' => $d['grupo'], 'descripcion' => $d['descripcion'], 'descripcion_corta' => $d['descripcion_corta'],
            'incluye_domingos' => $d['incluye_domingos'] === 'SI',
        ]);
    }

    protected function guardarTiposAsistencia(array $d): void
    {
        $this->upsert('plan_tipo_asistencias', ['codigo' => $d['codigo']], [
            'descripcion' => $d['descripcion'], 'horas_jornal' => (int) $d['horas_jornal'], 'color' => $d['color'], 'tipo' => $d['tipo'],
            'afecta_sueldo' => $d['afecta_sueldo'] === 'SI', 'porcentaje_remunerado' => (float) $d['porcentaje_remunerado'],
            'requiere_documento' => $d['requiere_documento'] === 'SI', 'acumula_vacaciones' => $d['acumula_vacaciones'] === 'SI',
            'acumula_asistencia' => $d['acumula_asistencia'] === 'SI', 'activo' => $d['activo'] === 'SI',
            'plan_tipo_suspension_id' => $d['tipo_suspension'] ? DB::table('plan_tipos_suspension')->where('codigo', $d['tipo_suspension'])->value('id') : null,
            'sin_suspension' => $d['sin_suspension'] === 'SI', 'rol' => $d['rol'], 'criterio_bono_asistencia' => $d['criterio_bono'],
        ]);
    }

    protected function guardarUnidadesProduccion(array $d): void
    {
        $this->upsert('produccion_unidades', ['codigo' => $d['codigo']], ['nombre' => $d['nombre'], 'decimales' => (int) $d['decimales'], 'activo' => $d['activo'] === 'SI']);
    }

    protected function guardarLabores(array $d): void
    {
        $actual = DB::table('labores')->where('codigo', $d['codigo'])->first();
        $valores = [
            'nombre_labor' => $d['nombre'], 'codigo_mano_obra' => $d['mano_obra'], 'tipo_asistencia_codigo' => $d['tipo_asistencia'],
            'rol_campania' => $d['rol_campania'], 'vigente_desde' => $d['vigente_desde'], 'estandar_produccion' => $d['estandar'],
            'registra_produccion' => $d['registra_produccion'] === 'SI', 'unidades' => $d['registra_produccion'] === 'SI' ? $d['unidades'] : null,
            'se_paga_con_jornal' => $d['se_paga_con_jornal'] === 'SI', 'updated_at' => now(),
        ];
        if ($d['activa'] === 'SI') {
            $valores += ['deleted_at' => null, 'eliminado_por' => null];
        } elseif (!$actual || !$actual->deleted_at) {
            $valores += ['deleted_at' => now(), 'eliminado_por' => auth()->id()];
        }
        $actual ? DB::table('labores')->where('id', $actual->id)->update($valores + ['actualizado_por' => auth()->id()])
            : DB::table('labores')->insert($valores + ['codigo' => (int) $d['codigo'], 'creado_por' => auth()->id(), 'created_at' => now()]);
    }

    protected function guardarRiego(array $d): void
    {
        $this->upsert('reg_labores', ['nombre_labor' => $d['nombre']], [
            'es_riego' => $d['es_riego'] === 'SI', 'es_apoyo_riego' => $d['es_apoyo_riego'] === 'SI', 'consumo_m3_hora' => $d['consumo_m3_hora'],
        ]);
    }

    protected function guardarNutrientes(array $d): void
    {
        $this->upsert('nutrientes', ['codigo' => $d['codigo']], ['nombre' => $d['nombre'], 'unidad' => $d['unidad']]);
    }

    protected function guardarConfiguracion(array $d): void
    {
        $this->upsert('configuracion', ['codigo' => $d['codigo']], ['valor' => $d['valor'] ?? '', 'descripcion' => $d['descripcion'] ?? '']);
    }

    /** Parámetros con vigencia: por el proceso de parámetros (motivo, "hasta" automático, planillas generadas). */
    protected function guardarConfiguracionFecha(array $d): void
    {
        app(PlanillaParametroProceso::class)->guardarDesdeExcel($d['codigo'], $d['valor'], $d['desde'], $d['motivo'] ?? null);
    }

    protected function guardarSp(array $d): void
    {
        $this->upsert('plan_sp_desc', ['codigo' => $d['codigo']], [
            'referencia' => $d['referencia'], 'orden' => $d['orden'], 'descripcion' => $d['descripcion'], 'tipo' => $d['tipo'],
            'porcentaje' => (float) $d['porcentaje'], 'porcentaje_65' => (float) $d['porcentaje_65'], 'color' => $d['color'],
        ]);
    }

    protected function guardarSpFecha(array $d): void
    {
        $this->upsert('plan_sp_desc_hist', ['descuento_codigo' => $d['codigo'], 'fecha_inicio' => $d['desde']], [
            'fecha_fin' => $d['hasta'], 'porcentaje' => (float) $d['porcentaje'], 'porcentaje_65' => (float) $d['porcentaje_65'],
        ]);
    }

    protected function guardarAfp(array $d): void
    {
        app(PlanillaParametroProceso::class)->guardarAfp($d['desde'], [$d['afp'] => $this->datosAfp($d)], $d['motivo'] ?? null);
    }

    private function datosAfp(array $d): array
    {
        return ['comision_flujo' => $d['comision_flujo'], 'comision_saldo' => $d['comision_saldo'], 'prima_seguros' => $d['prima_seguros'],
            'aporte_obligatorio' => $d['aporte_obligatorio'], 'remuneracion_maxima_asegurable' => $d['remuneracion_maxima']];
    }

    protected function guardarMaquinarias(array $d): void
    {
        $combustible = $d['combustible']
            ? DB::table('productos')->whereRaw('LOWER(nombre_comercial) = ?', [mb_strtolower($d['combustible'])])->orderBy('id')->value('id') : null;
        $this->upsert('maquinarias', ['nombre' => $d['nombre']], [
            'alias_blanco' => $d['alias_blanco'], 'combustible_producto_id' => $combustible, 'usa_distribucion' => $d['usa_distribucion'] === 'SI',
            'consumo_modo' => $d['consumo_modo'], 'consumo_estimado' => $d['consumo_modo'] ? $d['consumo_estimado'] : null, 'placa' => $d['placa'],
        ]);
    }

    protected function guardarGrupos(array $d): void
    {
        $actual = DB::table('cuad_grupos')->where('codigo', $d['codigo'])->first();
        $valores = ['nombre' => $d['nombre'], 'color' => $d['color'], 'modalidad_pago' => $d['modalidad_pago'],
            'costo_dia_sugerido' => (float) $d['costo_dia'], 'updated_at' => now()];
        if ($d['activo'] === 'SI') {
            $valores += ['deleted_at' => null, 'eliminado_por' => null];
        } elseif (!$actual || !$actual->deleted_at) {
            $valores += ['deleted_at' => now(), 'eliminado_por' => auth()->id()];
        }
        $actual ? DB::table('cuad_grupos')->where('codigo', $d['codigo'])->update($valores + ['actualizado_por' => auth()->id()])
            : DB::table('cuad_grupos')->insert($valores + ['codigo' => $d['codigo'], 'creado_por' => auth()->id(), 'created_at' => now()]);
    }

    protected function guardarObservaciones(array $d): void
    {
        $this->upsert('cochinilla_observaciones', ['codigo' => $d['codigo']], [
            'descripcion' => $d['descripcion'], 'es_cosecha_mama' => $d['es_cosecha_mama'] === 'SI', 'es_vendible' => $d['es_vendible'] === 'SI',
        ]);
    }

    protected function guardarUsuarios(array $d): void
    {
        $usuario = User::where('email', $d['correo'])->first() ?? new User(['email' => $d['correo']]);
        $usuario->name = $d['nombre'];
        $usuario->estado = $d['activo'] === 'SI';
        if ($d['clave'] !== null || !$usuario->exists) {
            $usuario->password = Hash::make($d['clave'] ?? SistemaRecursosReglas::claveInicial($d['correo']));
        }
        if (!$usuario->exists) {
            $usuario->email_verified_at = now();
        }
        $usuario->save();
        $usuario->syncRoles(self::lista($d['roles']));
    }
}
