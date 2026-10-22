<?php

namespace App\Services\Cochinilla\Excel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Excel oficial de un año armado desde el sistema (ingresos con sus sublotes, venteados y filtrados de esos lotes,
 * infestaciones del año). Las hojas que el sistema no guarda se toman del último archivo subido de ese año.
 */
class CochinillaExcelExportarConsulta
{
    public function __construct(private CochinillaExcelLibro $libro, private CochinillaExcelCatalogoConsulta $catalogo)
    {
    }

    public function libro(int $anio): Spreadsheet
    {
        return $this->libro->crear($anio, $this->datos($anio), $this->catalogo->listas());
    }

    public function datos(int $anio): array
    {
        $obs = $this->catalogo->observaciones();
        $ingresos = DB::table('cochinilla_ingresos')->whereYear('fecha', $anio)->orderBy('lote')->get();
        $detalles = DB::table('cochinilla_ingreso_detalles')->whereIn('cochinilla_ingreso_id', $ingresos->pluck('id'))->get()
            ->groupBy('cochinilla_ingreso_id');
        $campanias = DB::table('campos_campanias')->whereIn('id', $ingresos->pluck('campo_campania_id')->filter())->pluck('nombre_campania', 'id');

        $ingreso = [];
        foreach ($ingresos as $i) {
            $subs = ($detalles[$i->id] ?? collect())->sortBy(fn($d) => (int) (explode('.', $d->sublote_codigo)[1] ?? 0));
            foreach ($subs as $d) {
                $ingreso[] = ['lote' => null, 'sublote' => $d->sublote_codigo, 'fecha' => substr((string) $d->fecha, 0, 10), 'campo' => $i->campo,
                    'observacion' => $obs[$d->observacion] ?? $d->observacion, 'kilos' => (float) $d->total_kilos, 'campania' => null];
            }
            $ingreso[] = ['lote' => (int) $i->lote, 'sublote' => null, 'fecha' => substr((string) $i->fecha, 0, 10), 'campo' => $i->campo,
                'observacion' => $obs[$i->observacion] ?? $i->observacion, 'kilos' => (float) $i->total_kilos, 'campania' => $campanias[$i->campo_campania_id] ?? null];
        }

        // Venteado / filtrado: los de los lotes del año, y los sueltos (sin ingreso) procesados en el año
        $lotes = $ingresos->pluck('lote')->all();
        $procesos = fn(string $tabla) => DB::table($tabla)
            ->where(fn($q) => $q->whereIn('lote', $lotes)->orWhere(fn($q) => $q->whereNull('cochinilla_ingreso_id')->whereYear('fecha_proceso', $anio)))
            ->orderBy('lote')->orderBy('fecha_proceso')->orderBy('id')->get();

        $venteado = $procesos('cochinilla_venteados')->map(fn($v) => [
            'lote' => (int) $v->lote, 'fecha' => $v->fecha_proceso ? substr($v->fecha_proceso, 0, 10) : null, 'kilos' => (float) $v->kilos_ingresado,
            'limpia' => (float) $v->limpia, 'basura' => null, 'polvillo' => (float) $v->polvillo,
        ])->all();
        $filtrado = $procesos('cochinilla_filtrados')->map(fn($f) => [
            'lote' => (int) $f->lote, 'fecha' => $f->fecha_proceso ? substr($f->fecha_proceso, 0, 10) : null, 'kilos' => (float) $f->kilos_ingresados,
            'primera' => (float) $f->primera, 'segunda' => (float) $f->segunda, 'tercera' => (float) $f->tercera, 'piedra' => (float) $f->piedra, 'basura' => null,
        ])->all();

        $infestaciones = DB::table('cochinilla_infestaciones')->whereYear('fecha', $anio)->orderBy('fecha')->orderBy('id')->get();
        $campaniasInf = DB::table('campos_campanias')->whereIn('id', $infestaciones->pluck('campo_campania_id')->filter())->pluck('nombre_campania', 'id');
        $infestacion = $infestaciones->map(fn($i) => [
            'tipo' => $i->tipo_infestacion, 'fecha' => substr($i->fecha, 0, 10), 'campo' => $i->campo_nombre,
            'area' => $i->area !== null ? (float) $i->area : $this->catalogo->area($i->campo_nombre), 'campania' => $campaniasInf[$i->campo_campania_id] ?? null,
            'kg_madres' => (float) $i->kg_madres, 'origen' => $i->campo_origen_nombre, 'metodo' => $i->metodo,
            'envases' => (float) $i->numero_envases, 'capacidad' => (float) $i->capacidad_envase,
            'malla' => $i->porcentaje_malla_rashel !== null ? (float) $i->porcentaje_malla_rashel : null, 'procedencia' => $i->procedencia_infestadores,
        ])->all();

        return compact('ingreso', 'venteado', 'filtrado', 'infestacion') + ['extras' => $this->extras($anio)];
    }

    /** Hojas no importadas: del último archivo subido del año (o, sin archivo, vacías con los encabezados de otro año). */
    private function extras(int $anio): array
    {
        $archivo = DB::table('cochinilla_archivos_anuales')->where('anio', $anio)->latest('id')->first()
            ?? DB::table('cochinilla_archivos_anuales')->latest('id')->first();
        if (!$archivo || !Storage::disk('local')->exists($archivo->ruta)) {
            return [];
        }
        $extras = $this->libro->leer(Storage::disk('local')->path($archivo->ruta))['extras'];
        if ((int) $archivo->anio !== $anio) {
            $extras = array_map(fn($e) => ['encabezados' => $e['encabezados'], 'filas' => []], $extras);
        }
        return $extras;
    }

    public function anios(): array
    {
        $anios = DB::table('cochinilla_ingresos')->selectRaw('DISTINCT YEAR(fecha) a')->pluck('a')
            ->merge(DB::table('cochinilla_archivos_anuales')->pluck('anio'))
            ->push(Carbon::today()->year)->filter(fn($a) => $a >= \App\Services\Sistema\Vigencia\SistemaVigenciaReglas::anioMinimo(2015))->unique()->sortDesc()->values()->all();
        return $anios;
    }
}
