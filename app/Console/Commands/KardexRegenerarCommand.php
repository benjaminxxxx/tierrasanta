<?php

namespace App\Console\Commands;

use App\Models\InsKardex;
use App\Services\Almacen\Kardex\InsumoKardexMovimientosServicio;
use Illuminate\Console\Command;

/**
 * Regenera los movimientos de los kardex de un año (o los indicados). Usar después de cambiar cómo se ordenan o
 * calculan los movimientos (ej. 2026-11-12: el mismo día, primero las compras y luego las salidas).
 */
class KardexRegenerarCommand extends Command
{
    protected $signature = 'kardex:regenerar {--anio= : Año de los kardex (por defecto el actual)} {--id=* : Solo estos kardex}
        {--tipo-faltante= : Código SUNAT (tabla 5, ej. 06) para los productos de estos kardex que no tienen tipo de existencia}';

    protected $description = 'Regenera los movimientos y saldos de los kardex (mismo día: compras antes que salidas)';

    public function handle(InsumoKardexMovimientosServicio $servicio): int
    {
        // El producto completo: el kardex necesita su tipo de existencia y unidad (con columnas recortadas parecía no tenerlos)
        $query = InsKardex::query()->with('producto');
        $ids = array_filter((array) $this->option('id'));
        $ids ? $query->whereIn('id', $ids) : $query->where('anio', (int) ($this->option('anio') ?: now()->year));

        $kardexes = $query->orderBy('id')->get();

        if ($tipo = $this->option('tipo-faltante')) {
            $tipo = str_pad($tipo, 2, '0', STR_PAD_LEFT);
            if (!\Illuminate\Support\Facades\DB::table('sunat_tabla5_tipo_existencias')->where('codigo', $tipo)->exists()) {
                $this->error("{$tipo} no es un tipo de existencia SUNAT.");
                return self::FAILURE;
            }
            $sin = \App\Models\Producto::whereIn('id', $kardexes->pluck('producto_id'))->get()->filter(fn($p) => !$p->tabla5);
            foreach ($sin as $p) {
                $p->update(['codigo_tipo_existencia' => $tipo]);
            }
            $this->info("Tipo de existencia {$tipo} puesto a {$sin->count()} producto(s): " . $sin->pluck('nombre_comercial')->implode(', '));
            $kardexes->each->unsetRelation('producto');
        }

        $errores = 0;
        $avisos = [];
        $this->withProgressBar($kardexes, function (InsKardex $k) use ($servicio, &$errores, &$avisos) {
            try {
                $servicio->generarMovimientos($k);
                if ($servicio->aviso) {
                    $avisos[] = "Kardex {$k->id} ({$k->tipo}): {$servicio->aviso}";
                }
            } catch (\Throwable $e) {
                $errores++;
                $this->newLine();
                $this->error("Kardex {$k->id} ({$k->producto?->nombre_comercial}, {$k->tipo}): {$e->getMessage()}");
            }
        });
        $this->newLine();
        foreach ($avisos as $a) {
            $this->warn($a);
        }
        $this->info("Regenerados: " . ($kardexes->count() - $errores) . " · sin Excel por datos del producto: " . count($avisos) . " · con error: {$errores}");
        if ($avisos) {
            $this->line('Para poner el tipo de existencia que falta a esos productos y volver a generar: php artisan kardex:regenerar --anio=' . ($this->option('anio') ?: now()->year) . ' --tipo-faltante=06 (06 = suministros; confirmar con contabilidad).');
        }
        return $errores ? self::FAILURE : self::SUCCESS;
    }
}
