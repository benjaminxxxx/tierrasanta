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
}
