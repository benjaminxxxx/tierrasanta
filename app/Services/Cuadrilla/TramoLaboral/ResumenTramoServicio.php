<?php

namespace App\Services\Cuadrilla\TramoLaboral;

use App\Models\CuadRegistroDiario;
use App\Models\CuadResumenPorTramo;
use App\Models\CuadTramoLaboral;
use App\Models\CuaGrupo;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use Exception;
use Illuminate\Support\Carbon;

class ResumenTramoServicio
{
    public function cambiarCondicion($resumenId)
    {
        $resumenPorTramo = CuadResumenPorTramo::findOrFail($resumenId);
        $condiciones = [
            'Pendiente' => 'Pagado',
            'Pagado' => 'Pendiente',
        ];
        $fechas = [
            'Pendiente' => Carbon::now()->format('Y-m-d'),
            'Pagado' => null,
        ];
        $resumenPorTramo->update([
            'condicion' => $condiciones[$resumenPorTramo->condicion],
            'fecha' => $fechas[$resumenPorTramo->condicion]
        ]);
        return $resumenPorTramo;
    }
    /**
     * Actualiza los campos fecha y recibo de un resumen.
     *
     * @param  int   $id
     * @param  array $data
     * @return CuadResumenPorTramo
     * @throws Exception
     */
    public static function actualizar(int $id, array $data): CuadResumenPorTramo
    {
        try {
            $resumen = CuadResumenPorTramo::findOrFail($id);

            // Prepara los datos para la actualización
            $updateData = [
                'fecha' => $data['fecha'] ?? $resumen->fecha,
                // [!] Aplica mb_strtoupper solo si se provee un nuevo recibo
                'recibo' => isset($data['recibo'])
                    ? mb_strtoupper($data['recibo'], 'UTF-8')
                    : $resumen->recibo,
            ];

            $resumen->update($updateData);

            return $resumen;
        } catch (Exception $e) {
            throw new Exception("Error al actualizar resumen ID [$id]: " . $e->getMessage());
        }
    }
    /**
     * Genera o actualiza el cuadro resumen para un tramo laboral específico.
     * Consolida sueldos y gastos adicionales, acumulando saldos pendientes de tramos anteriores.
     */
    public function generarResumen(int $tramoId): void
    {
        $tramoLaboral = CuadTramoLaboral::findOrFail($tramoId);
        $tramoAnterior = $this->encontrarAcumuladoAnterior($tramoLaboral);

        // 1. Obtenemos todos los resúmenes pendientes del tramo anterior.
        $resumenesAnteriores = $tramoAnterior
            ? CuadResumenPorTramo::where('tramo_id', $tramoAnterior->id)
                ->where('condicion', 'Pendiente')
                ->get()
            : collect();

        // 2. Obtenemos todos los grupos que tienen actividad en el tramo actual.
        $gruposEnTramoActual = $tramoLaboral->gruposEnTramos()
            ->with('grupo')
            ->orderBy('orden')
            ->get();

        // 3. (CORRECCIÓN CLAVE #1) Unificamos los códigos de grupo: tanto los que tienen
        // actividad actual como los que solo tienen deudas pendientes. Esto resuelve el problema
        // de que no se arrastraban deudas si no había actividad nueva.
        $codigosGrupoActuales = $gruposEnTramoActual->pluck('codigo_grupo');
        $codigosGrupoAnteriores = $resumenesAnteriores->pluck('grupo_codigo');
        $todosLosCodigos = $codigosGrupoActuales->merge($codigosGrupoAnteriores)->unique();

        $dataParaUpsert = [];

        // 4. Iteramos sobre la lista unificada de códigos de grupo.
        foreach ($todosLosCodigos as $codigoGrupo) {
            $grupoEnTramo = $gruposEnTramoActual->firstWhere('codigo_grupo', $codigoGrupo);
            $grupo = $grupoEnTramo ? $grupoEnTramo->grupo : CuaGrupo::where('codigo', $codigoGrupo)->first();

            if (!$grupo)
                continue; // Si por alguna razón el grupo ya no existe, lo omitimos.

            $resumenesAnterioresDelGrupo = $resumenesAnteriores->where('grupo_codigo', $codigoGrupo);

            // 🔹 Calcular sueldos
            $sueldos = $this->calcularSueldos($tramoLaboral, $resumenesAnterioresDelGrupo, $grupo, $codigoGrupo, $tramoAnterior);
            $dataParaUpsert = array_merge($dataParaUpsert, $sueldos);

            // 🔹 Calcular adicionales
            $adicionales = $this->calcularAdicionales($tramoLaboral, $resumenesAnterioresDelGrupo, $grupo, $codigoGrupo, $tramoAnterior);
            $dataParaUpsert = array_merge($dataParaUpsert, $adicionales);
        }
        // 1. Obtenemos las "claves únicas" de los registros que acabamos de calcular.
        // Una clave puede ser: "COD01-sueldo-ANDRES (septiembre)"
        $clavesCalculadas = collect($dataParaUpsert)->map(function ($row) {
            return $row['grupo_codigo'] . '-' . $row['tipo'] . '-' . $row['descripcion'];
        });
        // 2. Obtenemos los registros que ya existen en la BD para este tramo.
        $resumenesActualesEnDB = CuadResumenPorTramo::where('tramo_id', $tramoLaboral->id)->get();

        // 3. Identificamos los IDs de los registros que están en la BD pero NO en el nuevo cálculo.
        // Estos son los registros obsoletos que debemos eliminar.
        $idsParaEliminar = $resumenesActualesEnDB
            ->filter(function ($registroExistente) use ($clavesCalculadas) {
                $claveExistente = $registroExistente->grupo_codigo . '-' . $registroExistente->tipo . '-' . $registroExistente->descripcion;
                // Si la clave del registro de la BD no está en la lista de claves calculadas,
                // significa que debe ser eliminado.
                return !$clavesCalculadas->contains($claveExistente);
            })
            ->pluck('id');

        // 4. Si encontramos registros para eliminar, los borramos.
        if ($idsParaEliminar->isNotEmpty()) {
            CuadResumenPorTramo::destroy($idsParaEliminar);
        }

        $this->upsertResumenes($dataParaUpsert, $tramoLaboral->id);
    }

    /**
     * Calcula los sueldos para un grupo, considerando modalidad de pago y acumulados.
     */
    private function calcularSueldos($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior = null)
    {
        $costosQuery = CuadRegistroDiario::where('codigo_grupo', $codigoGrupo)
            ->whereBetween('fecha', [$tramoLaboral->fecha_inicio, $tramoLaboral->fecha_fin]);

        if ($grupo->modalidad_pago === 'mensual') {
            return $this->calcularSueldosMensuales($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior, $costosQuery);
        }

        $totalCostosActual = $costosQuery->sum('costo_dia');
        $descripcion = $grupo->nombre;

        $registroAnterior = $resumenesAnteriores->firstWhere('descripcion', $descripcion);

        // La deuda pendiente es simplemente la deuda acumulada del registro anterior.
        $deudaPendienteAnterior = $registroAnterior->deuda_acumulada ?? 0;
        $deudaAcumuladaFinal = $deudaPendienteAnterior + $totalCostosActual;

        // Si no hay deuda nueva ni pendiente, no generamos un registro vacío.
        if ($deudaAcumuladaFinal == 0) {
            return [];
        }
        $fechaAcumulada = $registroAnterior->fecha_acumulada ?? $tramoLaboral->fecha_inicio;
        return [
            [
                'grupo_codigo' => $codigoGrupo,
                'color' => $grupo->color,
                'tipo' => 'sueldo',
                'descripcion' => $descripcion,
                'condicion' => 'Pendiente',
                'fecha' => null,
                'fecha_acumulada' => $fechaAcumulada,
                'recibo' => null,
                'deuda_actual' => $totalCostosActual,
                'deuda_acumulada' => $deudaAcumuladaFinal, // Deuda anterior + actual
                'tramo_id' => $tramoLaboral->id,
                'tramo_acumulado_id' => $tramoAnterior?->id,
                'modalidad_pago' => $grupo->modalidad_pago,
                'fecha_inicio' => $tramoLaboral->fecha_inicio,
                'fecha_fin' => $tramoLaboral->fecha_fin,
            ]
        ];
    }

    /**
     * Lógica específica para el cálculo de sueldos con modalidad 'mensual'.
     * ESTA VERSIÓN ESTÁ CORREGIDA para arrastrar deudas de meses anteriores aunque no tengan actividad actual.
     */
    private function calcularSueldosMensuales($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior, $costosQuery)
    {
        // 1. Pre-calculamos los costos del tramo actual, pero agrupados por el nombre del mes.
        // Ej: ['septiembre' => 180.42, 'octubre' => 360.84]
        $costosActualesPorMes = (clone $costosQuery)->get()
            ->groupBy(function ($registro) {
                // Agrupamos por el nombre del mes en español
                return Carbon::parse($registro->fecha)->translatedFormat('F');
            })
            ->map(function ($registrosDelMes) {
                // Sumamos el costo por cada mes
                return $registrosDelMes->sum('costo_dia');
            });

        // 2. Unificamos descripciones: las que vienen de deudas anteriores + las de costos actuales.
        $descripcionesAnteriores = $resumenesAnteriores->where('tipo', 'sueldo')->pluck('descripcion');
        $descripcionesNuevas = $costosActualesPorMes->keys()->map(fn($mes) => "{$grupo->nombre} ({$mes})");
        $todasLasDescripciones = $descripcionesAnteriores->merge($descripcionesNuevas)->unique()->values();

        $resultados = [];

        // 3. Iteramos sobre cada descripción (ej: "ANDRES (septiembre)", "ANDRES (octubre)").
        foreach ($todasLasDescripciones as $descripcion) {
            // Extraemos el nombre del mes de la descripción para buscar el costo actual.
            preg_match('/\((\p{L}+)\)/u', $descripcion, $matches); // \p{L}+ para letras unicode
            $mes = !empty($matches[1]) ? $matches[1] : null;

            // Buscamos el costo actual para ese mes (será 0 si no hubo actividad).
            $costoActual = $costosActualesPorMes->get($mes, 0);

            // Buscamos si había una deuda pendiente para esta descripción exacta.
            $registroAnterior = $resumenesAnteriores->firstWhere('descripcion', $descripcion);
            $deudaPendienteAnterior = $registroAnterior->deuda_acumulada ?? 0;

            // Calculamos la deuda final.
            $deudaAcumuladaFinal = $deudaPendienteAnterior + $costoActual;

            $fechaAcumulada = $registroAnterior->fecha_acumulada ?? $tramoLaboral->fecha_inicio;

            if ($deudaAcumuladaFinal > 0) {
                $resultados[] = [
                    'grupo_codigo' => $codigoGrupo,
                    'color' => $grupo->color,
                    'tipo' => 'sueldo',
                    'descripcion' => $descripcion,
                    'condicion' => 'Pendiente',
                    'fecha' => null,
                    'fecha_acumulada' => $fechaAcumulada,
                    'recibo' => null,
                    'deuda_actual' => $costoActual,
                    'deuda_acumulada' => $deudaAcumuladaFinal,
                    'tramo_id' => $tramoLaboral->id,
                    'tramo_acumulado_id' => $tramoAnterior?->id,
                    'modalidad_pago' => $grupo->modalidad_pago,
                    'fecha_inicio' => $tramoLaboral->fecha_inicio,
                    'fecha_fin' => $tramoLaboral->fecha_fin,
                ];
            }
        }
        return $resultados;
    }

    /**
     * (CORRECCIÓN CLAVE #2) Calcula los gastos adicionales consolidando actuales con pendientes.
     * Esta función fue refactorizada para corregir el cálculo de la deuda y simplificar la lógica.
     */
    private function calcularAdicionales($tramoLaboral, $resumenesAnteriores, $grupo, $codigoGrupo, $tramoAnterior)
    {
        $gastosActuales = GastoAdicionalPorGrupoCuadrilla::where('cuad_tramo_laboral_id', $tramoLaboral->id)
            ->where('codigo_grupo', $codigoGrupo)
            ->get();

        $adicionalesAnteriores = $resumenesAnteriores->where('tipo', 'adicional');

        // Unificamos las descripciones de los gastos actuales y los pendientes.
        $descripcionesActuales = $gastosActuales->pluck('descripcion');
        $descripcionesAnteriores = $adicionalesAnteriores->pluck('descripcion');
        $todasLasDescripciones = $descripcionesActuales->merge($descripcionesAnteriores)->unique();

        $resultados = [];

        foreach ($todasLasDescripciones as $descripcion) {
            $montoActual = $gastosActuales->where('descripcion', $descripcion)->sum('monto');
            $registroAnterior = $adicionalesAnteriores->firstWhere('descripcion', $descripcion);

            // La deuda pendiente es la deuda acumulada del registro anterior.
            // Este es el cálculo correcto.
            $deudaPendienteAnterior = $registroAnterior->deuda_acumulada ?? 0;
            $fechaAcumulada = $registroAnterior->fecha_acumulada ?? $tramoLaboral->fecha_inicio;

            // La nueva deuda acumulada es la suma de lo pendiente más lo actual.
            $deudaAcumuladaFinal = $deudaPendienteAnterior + $montoActual;

            if ($deudaAcumuladaFinal == 0)
                continue; // No generar registros en cero.

            $resultados[] = [
                'grupo_codigo' => $codigoGrupo,
                'color' => $grupo->color,
                'tipo' => 'adicional',
                'descripcion' => $descripcion,
                'condicion' => 'Pendiente',
                'fecha' => null,
                'fecha_acumulada' => $fechaAcumulada,
                'recibo' => null,
                'deuda_actual' => $montoActual,
                'deuda_acumulada' => $deudaAcumuladaFinal,
                'tramo_id' => $tramoLaboral->id,
                'tramo_acumulado_id' => $tramoAnterior?->id,
                'modalidad_pago' => $grupo->modalidad_pago,
                'fecha_inicio' => $tramoLaboral->fecha_inicio,
                'fecha_fin' => $tramoLaboral->fecha_fin,
            ];
        }

        return $resultados;
    }

    private function upsertResumenes(array $data, int $tramoId): void
    {
        if (empty($data)) {
            return;
        }

        $orden = 0;
        foreach ($data as $row) {
            // 1. Define los atributos que hacen único a un registro.
            $uniqueAttributes = [
                'tramo_id' => $tramoId,
                'grupo_codigo' => $row['grupo_codigo'],
                'descripcion' => $row['descripcion'],
                'tipo' => $row['tipo'],
            ];

            // 2. Busca si ya existe un registro con esa combinación única.
            $existingRecord = CuadResumenPorTramo::where($uniqueAttributes)->first();

            // 3. Prepara los datos que se van a guardar.
            $valuesToUpsert = $row;
            $valuesToUpsert['orden'] = ++$orden;

            // 4. [LA CLAVE] Si el registro ya existía, usamos sus valores guardados
            // para no sobrescribir los cambios manuales.
            if ($existingRecord) {
                $valuesToUpsert['fecha'] = $existingRecord->fecha;
                $valuesToUpsert['recibo'] = $existingRecord->recibo;
                $valuesToUpsert['condicion'] = $existingRecord->condicion;
            }

            // 5. Ejecuta la operación:
            // - Si no existía, crea un nuevo registro con todos los datos de $valuesToUpsert.
            // - Si ya existía, actualiza el registro pero ahora $valuesToUpsert contiene
            //   los valores de fecha, recibo y condicion que ya estaban en la base de datos.
            CuadResumenPorTramo::updateOrCreate($uniqueAttributes, $valuesToUpsert);
        }
    }

    /**
     * Encuentra el tramo laboral inmediatamente anterior al tramo actual.
     */
    private function encontrarAcumuladoAnterior(CuadTramoLaboral $tramo): ?CuadTramoLaboral
    {
        return CuadTramoLaboral::where('fecha_fin', '<', $tramo->fecha_inicio)
            ->orderBy('fecha_fin', 'desc')
            ->first();
    }
}