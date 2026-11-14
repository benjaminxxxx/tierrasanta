<?php

namespace App\Services\Almacen\Salida;

use App\Models\AlmacenProductoSalida;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Orden de las salidas dentro de su día (columna indice = 1, 2, 3…): el orden en que se llenaron manda en la lista y
 * en el kardex (el mismo día: primero las compras y luego las salidas en este orden).
 *
 * Al guardar un lote (tabla de salidas o importación del Excel del kardex), las salidas del lote quedan en el orden en
 * que venían (filas de la tabla o del Excel):
 *  - si el lote ya tenía salidas en ese día (se está editando un grupo), el lote ocupa el lugar donde estaba ese grupo
 *    y lo que venía después se corre: grupo 6–12 + 2 nuevas → 6–14, y lo que era 13–22 pasa a 15–24;
 *  - si todo el lote es nuevo en ese día, va al final.
 * Los días de donde se movió o eliminó una salida se renumeran sin huecos.
 *
 * Salidas antiguas sin indice: su orden es el de registro (id), igual que antes.
 */
class AlmacenSalidaOrdenProceso
{
    /**
     * @param array $lote [['id', 'fecha', 'fecha_antes' => ?string, 'indice_antes' => ?int], …] en el orden del lote
     * @param array $otrasFechas días que perdieron salidas (eliminadas o movidas a otro día)
     * @return int[] salidas cuyo lugar cambió (su kardex debe regenerarse)
     */
    public function ordenarLote(array $lote, array $otrasFechas = []): array
    {
        $cambiadas = [];
        $porFecha = [];
        foreach ($lote as $s) {
            $porFecha[self::dia($s['fecha'])][] = $s;
        }
        foreach ($porFecha as $fecha => $filas) {
            $cambiadas = array_merge($cambiadas, $this->ordenarDia($fecha, $filas));
        }
        foreach (array_unique(array_map([self::class, 'dia'], array_filter($otrasFechas))) as $fecha) {
            if (!isset($porFecha[$fecha])) {
                $cambiadas = array_merge($cambiadas, $this->ordenarDia($fecha, []));
            }
        }
        return array_values(array_unique($cambiadas));
    }

    /**
     * Renumera un día: las demás salidas en su orden actual y el lote (en su orden) en el lugar que ocupaba.
     *
     * @return int[] ids cuyo lugar relativo cambió
     */
    private function ordenarDia(string $fecha, array $lote): array
    {
        $idsLote = array_map(fn($s) => (int) $s['id'], $lote);
        // Orden actual del día: las del lote que ya eran de este día cuentan con su indice de antes
        $antes = [];
        foreach ($lote as $s) {
            if ($s['fecha_antes'] && self::dia($s['fecha_antes']) === $fecha) {
                $antes[(int) $s['id']] = $s['indice_antes'];
            }
        }
        $dia = AlmacenProductoSalida::whereDate('fecha_reporte', $fecha)->get(['id', 'indice'])
            ->map(fn($s) => ['id' => (int) $s->id, 'indice' => array_key_exists((int) $s->id, $antes) ? $antes[(int) $s->id] : $s->indice])
            ->filter(fn($s) => !in_array($s['id'], $idsLote, true) || array_key_exists($s['id'], $antes))
            ->sortBy(fn($s) => sprintf('%09d-%012d', $s['indice'] === null ? 999999999 : (int) $s['indice'], $s['id']))
            ->values();

        // Donde estaba el grupo: posición de la primera salida del lote que ya era de este día (si no, al final)
        $ancla = $dia->search(fn($s) => in_array($s['id'], $idsLote, true));
        $otras = $dia->reject(fn($s) => in_array($s['id'], $idsLote, true))->pluck('id')->values()->all();
        $antesDelGrupo = $ancla === false ? count($otras) : $dia->take($ancla)->reject(fn($s) => in_array($s['id'], $idsLote, true))->count();

        $orden = array_merge(array_slice($otras, 0, $antesDelGrupo), $idsLote, array_slice($otras, $antesDelGrupo));

        // Lugar relativo de antes (sin el lote nuevo) para saber qué salidas cambiaron de lugar
        $previo = $dia->pluck('id')->values()->all();
        $movidas = [];
        $enOrden = array_values(array_filter($orden, fn($id) => in_array($id, $previo, true)));
        foreach ($enOrden as $k => $id) {
            if (($previo[$k] ?? null) !== $id) {
                $movidas[] = $id;
            }
        }

        $actuales = AlmacenProductoSalida::whereIn('id', $orden)->pluck('indice', 'id');
        foreach ($orden as $posicion => $id) {
            if ((int) ($actuales[$id] ?? 0) !== $posicion + 1) {
                // Sin tocar updated_at ni la auditoría: es solo el orden
                DB::table('almacen_producto_salidas')->where('id', $id)->update(['indice' => $posicion + 1]);
            }
        }
        return $movidas;
    }

    private static function dia($fecha): string
    {
        return Carbon::parse($fecha)->toDateString();
    }
}
