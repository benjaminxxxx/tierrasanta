<?php
namespace App\Services\Campo\Labor;

use App\Models\Labores;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LaborServicio
{
    /**
     * Leer registros con filtros y paginación opcional
     */
    public static function leer(array $filtros = [], ?int $porPagina = null, bool $verEliminados = false)
    {
        $query = Labores::query();

        if ($verEliminados) {
            $query->onlyTrashed();
        }

        $query->when($filtros['buscar'] ?? null, function ($q, $buscar) {
            $q->where(function ($sub) use ($buscar) {
                $sub->where('nombre_labor', 'like', "%{$buscar}%")
                    ->orWhere('codigo', 'like', "%{$buscar}%");
            });
        })
            ->when($filtros['mano_obra'] ?? null, function ($q, $manoObra) {
                // 'sin' = labores sin mano de obra asignada (tarea pendiente)
                $manoObra === 'sin'
                    ? $q->where(fn($q2) => $q2->whereNull('codigo_mano_obra')->orWhere('codigo_mano_obra', ''))
                    : $q->where('codigo_mano_obra', $manoObra);
            })
            // 1. Filtro: Afecto a bono (Tiene o no tramos de bonificación)
            ->when($filtros['afecto_bono'] ?? null, function ($q, $afectoBono) {
                if ($afectoBono === 'con_tramos') {
                    $q->whereNotNull('tramos_bonificacion')
                        ->where('tramos_bonificacion', '!=', '')
                        ->where('tramos_bonificacion', '!=', '[]');
                } elseif ($afectoBono === 'sin_tramos') {
                    $q->where(function ($sub) {
                        $sub->whereNull('tramos_bonificacion')
                            ->orWhere('tramos_bonificacion', '')
                            ->orWhere('tramos_bonificacion', '[]');
                    });
                }
            })
            ->when($filtros['tipo'] ?? null, function ($q, $tipo) {
                $tipo === 'suspension'
                    ? $q->whereNotNull('tipo_asistencia_codigo')->where('tipo_asistencia_codigo', '<>', '')
                    : $q->where(fn($q2) => $q2->whereNull('tipo_asistencia_codigo')->orWhere('tipo_asistencia_codigo', ''));
            })
            // 2. Filtro: Método de bono (Se paga con el jornal o se acumula)
            ->when($filtros['metodo_bono'] ?? null, function ($q, $metodoBono) {
                if ($metodoBono === 'se_paga_con_jornal') {
                    $q->where('se_paga_con_jornal', true);
                } elseif ($metodoBono === 'se_acumula') {
                    $q->where('se_paga_con_jornal', false);
                }
            });

        $query->with('tipoAsistencia:codigo,descripcion,color')->latest();

        return $porPagina ? $query->paginate($porPagina) : $query->get();
    }

    public static function guardar(array $data, ?int $id = null)
    {
        return $id ? self::actualizar($id, $data) : self::crear($data);
    }

    public static function crear(array $data)
    {
        $validados = self::validarYLimpiar($data);
        $validados['creado_por'] = auth()->id();

        return Labores::create($validados);
    }

    public static function actualizar(int $id, array $data)
    {
        $labor = Labores::findOrFail($id);
        // El código es lo que guardan los registros: si ya se usa (o tiene historia), cambiarlo cambiaría la labor de
        // esos registros. Para que el código pase a ser otra labor está "Reasignar código".
        if (array_key_exists('codigo', $data) && (string) $data['codigo'] !== (string) $labor->codigo && ($motivo = self::motivoCodigoFijo($labor))) {
            throw ValidationException::withMessages(['codigo' => "No se puede cambiar el código {$labor->codigo}: {$motivo}. Para que este código sea otra labor usa Reasignar código."]);
        }
        $validados = self::validarYLimpiar($data, $id);
        $validados['actualizado_por'] = auth()->id();

        $labor->update($validados);
        return $labor;
    }

    /**
     * Dónde se usa el código de la labor. Los registros guardan el código (no hay llave foránea), así que una
     * labor con registros no se puede borrar: sus reportes antiguos dejarían de mostrar qué labor era.
     *
     * @return array<string, int> origen => filas (solo los que tienen)
     */
    public static function usos(int $codigo): array
    {
        $usos = [
            'registro diario de planilla' => DB::table('plan_detalles_horas')->where('codigo_labor', $codigo)->count(),
            'registro diario de cuadrilla' => DB::table('cuad_detalles_horas')->where('codigo_labor', $codigo)->count(),
            'actividades (bonos)' => DB::table('actividades')->where('codigo_labor', $codigo)->count(),
            'costos (BDD)' => DB::table('resumen_costo_diarios')->where('labor', $codigo)->count(),
        ];
        return array_filter($usos);
    }

    /** Cuántas filas usan cada código (para la lista, en una consulta por tabla). @return array<int, int> */
    /** Por qué el código de la labor ya no se puede cambiar (null = sí se puede: no tiene registros ni historia). */
    public static function motivoCodigoFijo(Labores $labor): ?string
    {
        if ($usos = self::usos((int) $labor->codigo)) {
            return 'tiene registros (' . collect($usos)->map(fn($n, $o) => number_format($n) . " en {$o}")->implode(', ') . ')';
        }
        if ($labor->vigente_desde || \App\Models\LaborVigencia::where('codigo', $labor->codigo)->exists()) {
            return 'tiene historia de labores anteriores';
        }
        return null;
    }

    public static function usosPorCodigo(array $codigos): array
    {
        $total = [];
        foreach ([['plan_detalles_horas', 'codigo_labor'], ['cuad_detalles_horas', 'codigo_labor'], ['actividades', 'codigo_labor'], ['resumen_costo_diarios', 'labor']] as [$tabla, $col]) {
            DB::table($tabla)->whereIn($col, $codigos)->groupBy($col)->selectRaw("{$col} as codigo, COUNT(*) as n")->get()
                ->each(function ($r) use (&$total) { $total[(int) $r->codigo] = ($total[(int) $r->codigo] ?? 0) + (int) $r->n; });
        }
        return $total;
    }

    /**
     * Una labor sin registros se elimina de verdad (su código queda libre). Una con registros solo se desactiva:
     * deja de ofrecerse para registrar, pero sigue existiendo para que los reportes antiguos muestren su nombre.
     *
     * @return string 'eliminada' | 'desactivada'
     */
    public static function eliminar(int $id): string
    {
        $labor = Labores::findOrFail($id);
        if (self::usos((int) $labor->codigo)) {
            $labor->update(['eliminado_por' => auth()->id()]);
            $labor->delete(); // soft delete = desactivada
            return 'desactivada';
        }
        $labor->forceDelete();
        return 'eliminada';
    }

    /**
     * Valida los datos y limpia el JSON de tramos
     */
    protected static function validarYLimpiar(array $data, ?int $id = null)
    {
        // "Seleccione un grupo" llega como '' (Livewire no pasa por ConvertEmptyStringsToNull): vacío = null, y la
        // mano de obra es obligatoria (es el grupo con que se arman los costos de producción de cada campaña).
        foreach (['codigo_mano_obra', 'estandar_produccion', 'unidades', 'tipo_asistencia_codigo'] as $campo) {
            if (array_key_exists($campo, $data) && $data[$campo] === '') {
                $data[$campo] = null;
            }
        }

        // Casilla nunca marcada (labor nueva): llega null y la columna no admite null. Si no viene (importación),
        // no se toca: así no se apaga en las labores que ya la tenían marcada
        if (array_key_exists('se_paga_con_jornal', $data) && $data['se_paga_con_jornal'] === null) {
            $data['se_paga_con_jornal'] = false;
        }

        $validator = Validator::make($data, [
            'nombre_labor' => 'required|string|max:255',
            'codigo' => 'required|integer|unique:labores,codigo,' . $id,
            'codigo_mano_obra' => 'required|exists:mano_obras,codigo',
            // Labor de suspensión: representa ese tipo de asistencia (DM, V, FR…). No se usa con A (trabajo).
            'tipo_asistencia_codigo' => 'nullable|not_in:A|exists:plan_tipo_asistencias,codigo',
            'estandar_produccion' => 'nullable|integer|min:0',
            'unidades' => 'nullable|string|max:20',
            'tramos_bonificacion' => 'nullable', // Se limpia abajo
            'se_paga_con_jornal' => 'boolean'
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'codigo_mano_obra.required' => 'Elige la mano de obra de la labor.',
            'unique' => 'El :attribute ya existe.',
            'exists' => 'La :attribute no es válida.',
            'integer' => 'El :attribute debe ser un número entero.',
            'min' => 'El :attribute no puede ser negativo.',
            'max' => 'El :attribute admite como máximo :max caracteres.',
            'boolean' => 'El campo :attribute debe ser sí o no.',
        ], [
            'nombre_labor' => 'nombre de labor',
            'codigo' => 'código',
            'codigo_mano_obra' => 'mano de obra',
            'tipo_asistencia_codigo' => 'asistencia que representa',
            'estandar_produccion' => 'estándar de producción',
            'se_paga_con_jornal' => 'se paga junto con el costo día',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $datosLimpios = $validator->validated();

        // Aplicamos tu lógica de limpieza de tramos
        $datosLimpios['tramos_bonificacion'] = self::filtrarTramosBonificacion($data['tramos_bonificacion'] ?? null);

        return $datosLimpios;
    }
    public static function eliminarExcepto(array $ids)
    {
        return Labores::whereNotIn('id', $ids)
            ->get()
            ->each(fn($labor) => self::eliminar($labor->id));
    }
    /**
     * Tu lógica de limpieza integrada
     */
    private static function filtrarTramosBonificacion($tramos): ?string
    {
        if (empty($tramos))
            return null;

        $decoded = is_string($tramos) ? json_decode($tramos, true) : $tramos;

        if (!is_array($decoded))
            return null;

        $filtered = array_filter($decoded, function ($item) {
            // Aseguramos que existan las llaves para evitar errores de índice
            return !empty($item['hasta']) || !empty($item['monto']);
        });

        return empty($filtered) ? null : json_encode(array_values($filtered));
    }
}