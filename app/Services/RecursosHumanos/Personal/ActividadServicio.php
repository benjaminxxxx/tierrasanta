<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\Actividad;
use App\Models\CuadDetalleHora;
use App\Models\Labores;
use App\Models\ReporteDiarioDetalle;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ActividadServicio
{
    public static function actualizarConfiguracionActividad($data, $actividadId = null)
    {
        $actividad = Actividad::findOrFail($actividadId);
        $actividad->update($data);
    }
    #region Labores
    /**
     * Crear o actualizar una labor.
     *
     * @param array $data  Datos de la labor: 
     *                      - 'codigo' (int) obligatorio y único
     *                      - 'estandar_produccion' (int|null)
     *                      - 'unidades' (string|null)
     *                      - 'tramos_bonificacion' (json|null)
     * @param int|null $labor_id  Si se pasa, actualiza la labor existente con ese ID. Si es null, crea nueva.
     * 
     * Nota:
     * El campo 'bono' está deprecado y se guarda siempre como 0.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @return Labores
     */
    public static function guardarLabor(array $data, ?int $labor_id = null)
    {
        // Validación básica del campo obligatorio
        if (empty($data['codigo'])) {
            throw ValidationException::withMessages([
                'codigo' => 'El campo código es obligatorio.'
            ]);
        }

        // Verificar unicidad del código (exceptuando el actual si es update)
        $query = Labores::where('codigo', $data['codigo']);
        if ($labor_id) {
            $query->where('id', '!=', $labor_id);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'codigo' => 'Ya existe una labor con este código.'
            ]);
        }

        $estandar_produccion = $data['estandar_produccion'] ?? null;
        $tramos_bonificacion = self::filtrarTramosBonificacion($data['tramos_bonificacion']);

        // Preparar campos
        $payload = [
            'codigo' => $data['codigo'],
            'estandar_produccion' => $estandar_produccion,
            'unidades' => $data['unidades'] ?? null,
            'tramos_bonificacion' => $tramos_bonificacion,
            'estado' => $data['estado'] ?? 1,
            'nombre_labor' => $data['nombre_labor'] ?? 'Sin nombre',
            'codigo_mano_obra' => $data['codigo_mano_obra'] ?? null,
        ];

        return DB::transaction(function () use ($payload, $labor_id) {
            if ($labor_id) {
                $labor = Labores::findOrFail($labor_id);
                $labor->update($payload);
            } else {
                $labor = Labores::create($payload);
            }

            return $labor;
        });
    }
    /**
     * Limpia el contenido de tramos_bonificacion, eliminando elementos vacíos.
     *
     * @param mixed $tramos Puede ser null, array o JSON string
     * @return string|null JSON limpio o null
     */
    public static function filtrarTramosBonificacion($tramos): ?string
    {
        if (empty($tramos)) {
            return null;
        }

        // Decodificar si viene como string
        if (is_string($tramos)) {
            $decoded = json_decode($tramos, true);
        } else {
            $decoded = $tramos;
        }

        // Validar array
        if (!is_array($decoded)) {
            return null;
        }

        // Filtrar los elementos realmente vacíos
        $filtered = array_filter($decoded, function ($item) {
            return !empty($item['hasta']) || !empty($item['monto']);
        });

        return empty($filtered) ? null : json_encode(array_values($filtered));
    }
    public static function habilitarLabor(int $laborId, bool $estado)
    {
        $labor = Labores::findOrFail($laborId);
        $labor->update(['estado' => $estado ? 1 : 0]);
        return $labor;
    }
    public static function eliminarLabor(int $laborId)
    {
        $labor = Labores::findOrFail($laborId);
        $labor->delete();
    }
    #endregion
    #region Actividades
    /**
     * Detecta actividades únicas (campo + labor) tanto de cuadrilla como de planilla para una fecha dada.
     * Luego sincroniza la tabla de actividades: crea nuevas, actualiza existentes y elimina las que ya no se encuentran.
     *
     * @param string $fecha Fecha en formato Y-m-d para detectar y crear actividades.
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function detectarYCrearActividades(string $fecha): void
    {
        if (!$fecha) {
            throw ValidationException::withMessages([
                'fecha' => 'Debe especificar una fecha para detectar y crear actividades.'
            ]);
        }
        
        $labores = Labores::all()->keyBy('codigo');

        // Detalles de CUADRILLA
        $detalleCuadrilla = CuadDetalleHora::whereHas('registroDiario', function ($query) use ($fecha) {
            $query->where('fecha', $fecha);
        })
            ->get(['campo_nombre', 'codigo_labor'])
            ->map(function ($item) {
                return [
                    'campo' => trim($item->campo_nombre),
                    'codigo_labor' => trim($item->codigo_labor),
                ];
            });

        // 2️⃣ Detalles de PLANILLA
        $detallePlanilla = ReporteDiarioDetalle::select('campo as campo', 'labor as codigo_labor')
            ->whereHas('reporteDiario', function ($query) use ($fecha) {
                $query->where('fecha', $fecha);
            })
            ->groupBy('campo', 'labor')
            ->get()
            ->map(function ($item) {
                return [
                    'campo' => trim($item->campo),
                    'codigo_labor' => trim($item->codigo_labor),
                ];
            });

        // 3️⃣ Unir ambas listas y eliminar duplicados
        $paresUnicos = collect()
            ->merge($detalleCuadrilla)
            ->merge($detallePlanilla)
            ->filter(function ($item) {
                return $item['campo'] !== '' && $item['codigo_labor'] !== '';
            })
            ->unique(function ($item) {
                return $item['campo'] . '-' . $item['codigo_labor'];
            })
            ->values();

        if ($paresUnicos->isEmpty()) {
            return;
        }

        // 4️⃣ Obtener actividades existentes para esa fecha
        $actividadesExistentes = Actividad::where('fecha', $fecha)->get();

        $clavesNuevas = $paresUnicos->map(fn($item) => $item['campo'] . '-' . $item['codigo_labor']);

        // 5️⃣ Eliminar actividades que ya no están
        $actividadesAEliminar = $actividadesExistentes->filter(function ($actividad) use ($clavesNuevas) {
            return !$clavesNuevas->contains($actividad->campo . '-' . $actividad->codigo_labor);
        });

        foreach ($actividadesAEliminar as $actividad) {
            $actividad->delete();
        }

        // 6️⃣ Crear o actualizar actividades nuevas
        foreach ($paresUnicos as $i => $par) {
            $campo = $par['campo'];
            $codigoLabor = $par['codigo_labor'];

            if (!$labores->has($codigoLabor)) {
                throw ValidationException::withMessages([
                    "actividades.$i" => "El código de labor '$codigoLabor' no existe en el catálogo de labores."
                ]);
            }

            /** @var \App\Models\Labores $labor */
            $labor = $labores->get($codigoLabor);

            $matchQuery = Actividad::where('fecha', $fecha)
                ->where('campo', $campo)
                ->where('codigo_labor', $codigoLabor);

            $actividad = $matchQuery->first();

            $data = [];

            if ($actividad) {
                $actividad->update($data);
            } else {
                Actividad::create(array_merge($data, [
                    'unidades' => $labor->unidades ?? 0,
                    'codigo_labor' => $codigoLabor,
                    'nombre_labor' => $labor->nombre_labor,
                    'fecha' => $fecha,
                    'campo' => $campo,
                    'labor_id' => $labor->id,
                ]));
            }
        }
    }

    #endregion
}
