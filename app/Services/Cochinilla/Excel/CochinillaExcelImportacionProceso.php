<?php

namespace App\Services\Cochinilla\Excel;

use App\Models\CochinillaInfestacion;
use App\Models\CochinillaIngreso;
use App\Models\CochinillaIngresoDetalle;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Importa el Excel oficial de un año ya revisado en la vista previa: lo vuelve a leer y analizar, y aplica solo lo
 * nuevo o cambiado sin errores (ver CochinillaExcelImportacionConsulta):
 * - Ingreso: crea o actualiza el lote y sincroniza sus sublotes (los que ya no están en el Excel se quitan). La campaña
 *   la asignan los triggers por campo + fecha; la campaña dueña se recalcula sola (CochinillaIngreso::booted).
 * - Venteado / filtrado: reemplaza las filas de cada lote presente en el Excel (los triggers los enlazan al ingreso).
 * - Infestación: reemplaza las filas de cada campo + fecha presente en el Excel.
 * Guarda el archivo como el vigente del año (sus hojas no importadas se conservan para el próximo exportar).
 */
class CochinillaExcelImportacionProceso
{
    public function __construct(
        private CochinillaExcelLibro $libro,
        private CochinillaExcelImportacionConsulta $consulta,
    ) {
    }

    public function importar(string $ruta, int $anio, ?string $nombreOriginal = null): array
    {
        $analisis = $this->consulta->analizar($this->libro->leer($ruta), $anio);
        $areas = DB::table('campos')->pluck('area', 'nombre');
        $r = ['lotes_nuevos' => 0, 'lotes_actualizados' => 0, 'venteado' => 0, 'filtrado' => 0, 'infestacion' => 0];

        DB::transaction(function () use ($analisis, $areas, &$r, $anio) {
            foreach ($analisis['ingreso']['lotes'] as $l) {
                if (!in_array($l['estado'], ['nuevo', 'cambia'], true)) {
                    continue;
                }
                $ingreso = CochinillaIngreso::firstOrNew(['lote' => $l['lote']]);
                $antes = $ingreso->exists ? $ingreso->only(['fecha', 'campo', 'observacion', 'total_kilos']) : null;
                $ingreso->fill([
                    'fecha' => $l['fecha'], 'campo' => $l['campo'], 'area' => $areas[$l['campo']] ?? $ingreso->area,
                    'observacion' => $l['observacion'], 'total_kilos' => $l['total'],
                ]);
                $ingreso->stock_disponible ??= $l['total'];
                $ingreso->save();

                CochinillaIngresoDetalle::where('cochinilla_ingreso_id', $ingreso->id)->whereNotIn('sublote_codigo', array_keys($l['sublotes']))->delete();
                foreach ($l['sublotes'] as $codigo => $s) {
                    CochinillaIngresoDetalle::updateOrCreate(
                        ['cochinilla_ingreso_id' => $ingreso->id, 'sublote_codigo' => (string) $codigo],
                        ['fecha' => $s['fecha'], 'total_kilos' => $s['kilos'], 'observacion' => $s['observacion']],
                    );
                }
                AuditoriaServicio::registrar(
                    modelo: CochinillaIngreso::class,
                    modeloId: $ingreso->id,
                    accion: $antes ? 'editar' : 'crear',
                    antes: $antes,
                    despues: $ingreso->only(['fecha', 'campo', 'observacion', 'total_kilos']) + ['sublotes' => count($l['sublotes'])],
                    observacion: "Importación Excel cochinilla {$anio}" . ($l['cambios'] ? ': ' . implode('; ', $l['cambios']) : ''),
                );
                $antes ? $r['lotes_actualizados']++ : $r['lotes_nuevos']++;
            }

            foreach ([['venteado', 'cochinilla_venteados'], ['filtrado', 'cochinilla_filtrados']] as [$clave, $tabla]) {
                foreach ($analisis[$clave]['grupos'] as $g) {
                    if (!in_array($g['estado'], ['nuevo', 'cambia'], true)) {
                        continue;
                    }
                    DB::table($tabla)->where('lote', $g['lote'])->delete();
                    DB::table($tabla)->insert(array_map(fn($f) => $clave === 'venteado'
                        ? ['lote' => $g['lote'], 'fecha_proceso' => $f['fecha'], 'kilos_ingresado' => $f['kilos'], 'limpia' => $f['limpia'], 'polvillo' => $f['polvillo'],
                            'created_at' => now(), 'updated_at' => now()]
                        : ['lote' => $g['lote'], 'fecha_proceso' => $f['fecha'], 'kilos_ingresados' => $f['kilos'], 'primera' => $f['primera'], 'segunda' => $f['segunda'],
                            'tercera' => $f['tercera'], 'piedra' => $f['piedra'], 'created_at' => now(), 'updated_at' => now()], $g['filas']));
                    $r[$clave] += count($g['filas']);
                }
            }

            foreach ($analisis['infestacion']['grupos'] as $g) {
                if (!in_array($g['estado'], ['nuevo', 'cambia'], true)) {
                    continue;
                }
                // Con el modelo: al borrar / crear se recalcula la campaña dueña de los ingresos (CochinillaInfestacion::booted)
                CochinillaInfestacion::whereDate('fecha', $g['fecha'])->where('campo_nombre', $g['campo'])->get()->each->delete();
                foreach ($g['filas'] as $f) {
                    // Por hectárea, infestadores y madres por infestador los calcula el modelo (accessors)
                    CochinillaInfestacion::create([
                        'tipo_infestacion' => $f['tipo'], 'fecha' => $f['fecha'], 'campo_nombre' => $f['campo'], 'area' => $areas[$f['campo']] ?? null,
                        'kg_madres' => $f['kg_madres'], 'campo_origen_nombre' => $f['origen'], 'metodo' => $f['metodo'],
                        'numero_envases' => $f['envases'], 'capacidad_envase' => $f['capacidad'],
                        'porcentaje_malla_rashel' => $f['malla'], 'procedencia_infestadores' => $f['procedencia'],
                    ]);
                    $r['infestacion']++;
                }
            }
        });

        // Archivo vigente del año
        $destino = "cochinilla/anuales/{$anio}/" . now()->format('Ymd_His') . '_' . CochinillaExcelReglas::nombreArchivo($anio);
        Storage::disk('local')->put($destino, file_get_contents($ruta));
        DB::table('cochinilla_archivos_anuales')->insert([
            'anio' => $anio, 'ruta' => $destino, 'nombre_original' => $nombreOriginal, 'resumen' => json_encode($r + ['analisis' => $analisis['resumen']]),
            'subido_por' => auth()->id(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $r;
    }
}
