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
    protected $signature = 'kardex:regenerar {--anio= : Año de los kardex (por defecto el actual)} {--id=* : Solo estos kardex}';

    protected $description = 'Regenera los movimientos y saldos de los kardex (mismo día: compras antes que salidas)';

    public function handle(InsumoKardexMovimientosServicio $servicio): int
    {
        $query = InsKardex::query()->with('producto:id,nombre_comercial');
        $ids = array_filter((array) $this->option('id'));
        $ids ? $query->whereIn('id', $ids) : $query->where('anio', (int) ($this->option('anio') ?: now()->year));

        $kardexes = $query->orderBy('id')->get();
        $errores = 0;
        $this->withProgressBar($kardexes, function (InsKardex $k) use ($servicio, &$errores) {
            try {
                $servicio->generarMovimientos($k);
            } catch (\Throwable $e) {
                $errores++;
                $this->newLine();
                $this->error("Kardex {$k->id} ({$k->producto?->nombre_comercial}, {$k->tipo}): {$e->getMessage()}");
            }
        });
        $this->newLine();
        $this->info("Regenerados: " . ($kardexes->count() - $errores) . " · con error: {$errores}");
        return $errores ? self::FAILURE : self::SUCCESS;
    }
}
