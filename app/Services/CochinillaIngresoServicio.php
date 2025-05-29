<?php

namespace App\Services;

use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use Exception;

class CochinillaIngresoServicio
{


    public function __construct()
    {

    }
    /**
     * Este método se encarga de estandarizar los registros de ingresos de cochinilla que provienen de fuentes externas como archivos Excel.
     * En muchos casos, estos registros solo contienen información del ingreso principal sin sus detalles asociados, lo cual genera
     * inconsistencias en la gestión de datos (como el paginado, visualización y manipulación uniforme en la interfaz).
     *
     * Para corregir esto, este método identifica todos los ingresos que no tienen detalles registrados y les crea un detalle base
     * de manera automática. Este detalle incluirá los datos esenciales del ingreso (fecha, total de kilos, observación) y asignará
     * un código de sublote inicial siguiendo el formato "<lote>.1".
     *
     * Esta estandarización permite que todos los ingresos del sistema mantengan una estructura homogénea, facilitando así su manejo
     * en consultas, reportes, interfaces y procesos posteriores.
     *
     * @return void
     */

    public static function estandarizarIngresos()
    {
        $ingresos = CochinillaIngreso::doesntHave('detalles')->get();

        if ($ingresos) {
            foreach ($ingresos as $ingreso) {

                CochinillaIngresoDetalle::create([
                    'cochinilla_ingreso_id' => $ingreso->id,
                    'sublote_codigo' => $ingreso->lote . '.1',
                    'fecha' => $ingreso->fecha,
                    'total_kilos' => $ingreso->total_kilos,
                    'observacion' => $ingreso->observacion,
                ]);
            }
        }
    }
    /**
     * Genera el siguiente código de sublote disponible para un nuevo ingreso de cochinilla.
     *
     * Este método busca el último ingreso registrado (ordenado por el campo 'lote' en forma descendente)
     * y luego consulta el detalle más reciente asociado a ese ingreso, basándose en el sublote_codigo.
     * Si encuentra detalles, incrementa el número decimal del último sublote (por ejemplo, de "123.3" a "123.4").
     * Si no encuentra ningún detalle para ese lote, significa que es un nuevo ingreso no fragmentado, por lo que
     * se asigna un nuevo lote entero (por ejemplo, de "123" a "124").
     *
     * Este enfoque asegura que cada ingreso y sublote mantenga una secuencia única y coherente,
     * permitiendo un seguimiento claro de los movimientos y fragmentaciones del lote principal.
     *
     * @return string Código del siguiente sublote (ej. "123.4" o "124.1")
     */

    public static function generarCodigoSiguiente(): string
    {
        $ultimoIngreso = CochinillaIngreso::latest('lote')->first();

        if ($ultimoIngreso && $ultimoIngreso->detalles()->exists()) {
            $ultimoDetalle = $ultimoIngreso->detalles()
                ->orderByRaw('CAST(SUBSTRING_INDEX(sublote_codigo, ".", -1) AS UNSIGNED) DESC')
                ->first();

            $partes = explode('.', $ultimoDetalle->sublote_codigo);
            $loteBase = $partes[0];
            $numero = isset($partes[1]) ? ((int) $partes[1] + 1) : 1;

            return $loteBase . '.' . $numero;
        }

        // Si no hay detalle, usamos el siguiente lote entero
        $nuevoLote = $ultimoIngreso ? ((int) $ultimoIngreso->lote + 1) : 1;
        return (string) $nuevoLote;
    }
    /**
     * Registra o actualiza un sublote de ingreso de cochinilla.
     *
     * Este método se encarga de manejar de forma segura y estandarizada el registro de ingresos por sublote:
     *
     * - No se permite registrar ingresos directamente como lote principal (por ejemplo, "123"), ya que todos deben tener al menos un sublote ("123.1", "123.2", etc.).
     * - Si el ingreso principal (parte entera del sublote) no existe, se crea automáticamente.
     * - Si ya existe, se valida que el campo del nuevo sublote coincida con el campo del ingreso existente.
     *   - Si no coincide, se lanza una excepción para evitar errores por ingreso en campo incorrecto.
     * - El área del ingreso principal se actualiza cada vez que se registra un nuevo sublote, utilizando el valor más reciente del sublote registrado.
     * - Luego se registra o actualiza el sublote (detalle).
     * - Finalmente, el ingreso principal se actualiza con:
     *   - la suma total de kilos de todos los sublotes registrados,
     *   - el valor de observación del sublote más reciente (ordenado por sublote_codigo).
     *
     * Este proceso garantiza integridad y coherencia en la base de datos, evitando duplicidad, errores humanos y
     * manteniendo una estructura normalizada que facilita la consulta, el análisis y la visualización de los datos.
     *
     * @param array $datos
     *   - 'lote': string con formato decimal (ej. "123.1")
     *   - 'fecha': string en formato 'Y-m-d'
     *   - 'campo': string (nombre del campo)
     *   - 'area': string (área correspondiente al sublote)
     *   - 'campo_campania_id': int (relación con la campaña del campo)
     *   - 'observacion': string (observación del sublote)
     *   - 'total_kilos': float (cantidad de kilos en este sublote)
     *
     * @throws \Exception si se intenta registrar un lote principal directamente (sin sublote decimal).
     * @throws \Exception si el campo del sublote no coincide con el del ingreso principal ya existente.
     */
    public static function registrarDetalle(array $datos): CochinillaIngresoDetalle
    {
        if (!str_contains($datos['lote'], '.')) {
            throw new Exception('Debe registrar un sublote como 123.1, 123.2, etc. No se permite un lote principal sin sublote.');
        }

        [$lotePrincipal, $subloteDecimal] = explode('.', $datos['lote']);
        $loteEntero = (int) $lotePrincipal;

        // Buscar o crear el ingreso principal
        $ingreso = CochinillaIngreso::where('lote', $loteEntero)->first();

        if ($ingreso) {
            // Validar campo coincidente
            if ($ingreso->campo !== $datos['campo']) {
                throw new Exception("El sublote {$datos['lote']} pertenece al campo '{$ingreso->campo}', no puede registrarlo como '{$datos['campo']}'.");
            }
        } else {
            // Crear el ingreso principal si no existe
            $ingreso = CochinillaIngreso::create([
                'lote' => $loteEntero,
                'fecha' => $datos['fecha'],
                'campo' => $datos['campo'],
                'area' => $datos['area'],
                'campo_campania_id' => $datos['campo_campania_id'],
                'observacion' => $datos['observacion'],
                'total_kilos' => 0,
            ]);
        }

        // Crear o actualizar el detalle
        if (!empty($datos['cochinillaIngresoDetalleId'])) {
            // Actualizar detalle existente
            $detalle = CochinillaIngresoDetalle::findOrFail($datos['cochinillaIngresoDetalleId']);

            if ($detalle->cochinilla_ingreso_id !== $ingreso->id) {
                throw new Exception("El detalle no pertenece al ingreso con lote {$loteEntero}.");
            }

            $detalle->update([
                'fecha' => $datos['fecha'],
                'total_kilos' => $datos['total_kilos'],
                'observacion' => $datos['observacion'],
            ]);
        } else {
            // Crear nuevo detalle (si no existe)
            $detalle = CochinillaIngresoDetalle::updateOrCreate(
                [
                    'cochinilla_ingreso_id' => $ingreso->id,
                    'sublote_codigo' => $datos['lote'],
                ],
                [
                    'fecha' => $datos['fecha'],
                    'total_kilos' => $datos['total_kilos'],
                    'observacion' => $datos['observacion'],
                ]
            );
        }

        // Recalcular el ingreso principal
        $detalles = $ingreso->detalles()->orderBy('sublote_codigo')->get();

        $ingreso->update([
            'area' => $datos['area'], // Puedes cambiar esto si el área también depende del último detalle
            'fecha' => $detalles->max('fecha'), // Fecha más reciente de los sublotes
            'observacion' => $detalles->sortByDesc('fecha')->first()?->observacion ?? $ingreso->observacion, // Observación del detalle más reciente
            'total_kilos' => $detalles->sum('total_kilos'),
        ]);


        return $detalle;
    }




    public static function obtenerUltimoIngreso(): ?CochinillaIngreso
    {
        return CochinillaIngreso::orderByDesc('lote')->first();
    }


}
