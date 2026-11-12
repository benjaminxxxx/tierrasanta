<?php

namespace App\Console\Commands;

use App\Models\CampoCampania;
use App\Models\CochinillaIngreso;
use App\Services\Cochinilla\Origen\CochinillaOrigenProceso;
use App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula el campo y la campaña dueña de todos los ingresos de cochinilla con la regla de CochinillaOrigenProceso
 * (los infestadores son del campo con el que se registraron). Se corrió una vez el 12/11/2026 para corregir los lotes
 * asignados con la regla anterior; sirve también después de corregir infestaciones o campañas en bloque.
 */
class CochinillaRecalcularOrigenCommand extends Command
{
    protected $signature = 'cochinilla:recalcular-origen {--simular : muestra qué cambia y no guarda nada}';

    protected $description = 'Recalcula la campaña dueña de los ingresos de cochinilla (infestadores = campo que dio la mamá)';

    public function handle(CochinillaOrigenProceso $proceso): int
    {
        $nombre = fn($id) => $id ? (($c = CampoCampania::find($id)) ? "{$c->campo} {$c->nombre_campania}" : "#{$id}") : 'sin campaña';
        $antes = CochinillaIngreso::where('observacion', 'like', 'infestador%')->get(['id', 'lote', 'fecha', 'campo', 'total_kilos', 'campo_origen', 'campania_origen_id'])->keyBy('id');

        DB::beginTransaction();
        try {
            $n = $proceso->recalcularTodos();
            $despues = CochinillaIngreso::whereIn('id', $antes->keys())->get(['id', 'campania_origen_id', 'origen_estado', 'origen_detalle'])->keyBy('id');

            $cambios = $antes->filter(fn($a) => (int) $a->campania_origen_id !== (int) $despues[$a->id]->campania_origen_id);
            $this->info("Ingresos recalculados: {$n}. De infestadores: {$antes->count()}; cambian de campaña dueña: {$cambios->count()} ("
                . number_format($cambios->sum('total_kilos'), 2) . ' kg).');

            $porTraslado = $cambios->groupBy(fn($a) => $nombre($a->campania_origen_id) . ' → ' . $nombre($despues[$a->id]->campania_origen_id))
                ->map(fn($g) => [$g->count(), $g->sum('total_kilos'), $g->pluck('lote')->implode(', ')])
                ->sortByDesc(fn($v) => $v[1]);
            $this->table(['Antes → ahora', 'Lotes', 'Kg', 'Lotes (nº)'], $porTraslado->map(fn($v, $k) => [$k, $v[0], number_format($v[1], 2), mb_strimwidth($v[2], 0, 60, '…')])->values()->all());

            $revisar = $despues->where('origen_estado', 'sin_infestacion');
            if ($revisar->isNotEmpty()) {
                $this->warn("Para revisar (el campo no dio mamá en los " . CochinillaOrigenProceso::DIAS_RETORNO . ' días anteriores al lote):');
                foreach ($revisar as $r) {
                    $a = $antes[$r->id];
                    $this->line("  lote {$a->lote} · " . substr((string) $a->fecha, 0, 10) . " · campo {$a->campo} · " . number_format($a->total_kilos, 2) . ' kg');
                }
            }

            if ($this->option('simular')) {
                DB::rollBack();
                $this->comment('Simulación: no se guardó nada. Ejecuta sin --simular para aplicar.');
                return self::SUCCESS;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        app(CochinillaRecuperacionDetector::class)->detectarTareasPendientes();
        $this->info('Guardado. Se actualizó la tarea pendiente "Cochinilla por recuperar".');
        return self::SUCCESS;
    }
}
