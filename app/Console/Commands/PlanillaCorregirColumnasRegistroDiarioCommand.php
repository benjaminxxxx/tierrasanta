<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrige plan_resumen_diario.total_actividades cuando es menor que los tramos guardados de algún trabajador
 * (base pasada del servidor antiguo con el valor en 1). Con el valor bajo, el registro diario ocultaba tramos.
 * También deja el orden de los tramos (venían todos con orden 0) según su hora de inicio.
 */
class PlanillaCorregirColumnasRegistroDiarioCommand extends Command
{
    protected $signature = 'planilla:corregir-columnas-registro-diario {--verificar : Solo informa, no cambia nada}';

    protected $description = 'Ajusta las columnas de tramos del registro diario a los tramos guardados y ordena los tramos';

    public function handle(): int
    {
        $dias = DB::select('SELECT rd.fecha, MAX(t.n) AS tramos, COALESCE(MAX(res.total_actividades), 0) AS columnas
            FROM (SELECT plan_reg_dia_id, COUNT(*) AS n FROM plan_detalles_horas GROUP BY plan_reg_dia_id) t
            JOIN plan_registros_diarios rd ON rd.id = t.plan_reg_dia_id
            LEFT JOIN plan_resumen_diario res ON res.fecha = rd.fecha
            GROUP BY rd.fecha HAVING tramos > columnas ORDER BY rd.fecha');

        $sinOrden = DB::table('plan_detalles_horas')->where('orden', 0)->count();

        $this->info(count($dias) . ' día(s) con menos columnas que tramos; ' . $sinOrden . ' tramo(s) sin orden.');
        foreach (array_slice($dias, 0, 10) as $d) {
            $this->line("  {$d->fecha}: {$d->tramos} tramos, {$d->columnas} columna(s)");
        }

        if ($this->option('verificar') || (!$dias && !$sinOrden)) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($dias) {
            foreach ($dias as $d) {
                DB::table('plan_resumen_diario')->updateOrInsert(['fecha' => $d->fecha], ['total_actividades' => $d->tramos, 'updated_at' => now()]);
            }
            // Orden 1, 2, 3… por hora de inicio dentro de cada registro
            DB::statement('UPDATE plan_detalles_horas d JOIN (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY plan_reg_dia_id ORDER BY hora_inicio, id) AS n FROM plan_detalles_horas
                ) o ON o.id = d.id
                SET d.orden = o.n WHERE d.orden = 0');
        });

        $this->info('Listo.');
        return self::SUCCESS;
    }
}
