<?php

namespace App\Console\Commands;

use App\Services\Campo\Produccion\CampoProduccionReglas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara los datos para la producción de campo (una vez por base, se puede repetir):
 *  1. Catálogo de unidades con las que ya se usan (KG; LAVADOR une "lavadores" y "lavaderos"; HA…).
 *  2. Copia las cantidades de las tablas antiguas (cuad_produccion_actividades, plan_actividad_producciones) al registro
 *     de producción (con bono), si existen.
 *  3. Labores: "registra producción" = la que tiene cantidades registradas, estándar o tramos de bono; su unidad pasa al
 *     código del catálogo. Las demás quedan sin unidad (antes muchas decían "KG" sin producir nada).
 *   php artisan produccion:preparar --simular   (solo muestra qué cambiaría)
 */
class ProduccionPrepararCommand extends Command
{
    protected $signature = 'produccion:preparar {--simular : Solo muestra los cambios}';

    protected $description = 'Catálogo de unidades, labores que registran producción y copia de cantidades antiguas';

    private const NOMBRES = ['KG' => ['Kilogramos', 2], 'LAVADOR' => ['Lavadores', 0], 'HA' => ['Hectáreas', 2], 'CAJA' => ['Cajas', 0]];

    public function handle(): int
    {
        $simular = (bool) $this->option('simular');
        $copiadas = 0;

        DB::beginTransaction();
        try {
            // 1. Catálogo con las unidades usadas en labores, sus vigencias y las actividades
            $usadas = collect()
                ->merge(DB::table('labores')->pluck('unidades'))
                ->merge(Schema::hasTable('labor_vigencias') ? DB::table('labor_vigencias')->pluck('unidades') : [])
                ->merge(DB::table('actividades')->distinct()->pluck('unidades'))
                ->map(fn($u) => CampoProduccionReglas::codigo($u))->filter()->unique()->values();
            foreach ($usadas as $codigo) {
                if (!DB::table('produccion_unidades')->where('codigo', $codigo)->exists()) {
                    [$nombre, $dec] = self::NOMBRES[$codigo] ?? [ucfirst(mb_strtolower($codigo)), 2];
                    DB::table('produccion_unidades')->insert(['codigo' => $codigo, 'nombre' => $nombre, 'decimales' => $dec, 'activo' => true,
                        'created_at' => now(), 'updated_at' => now()]);
                    $this->line("Unidad nueva: {$codigo} ({$nombre})");
                }
            }

            // 2. Cantidades de las tablas antiguas → registro de producción (con bono)
            foreach (['cuad_produccion_actividades' => ['cuad_bonos_actividades', 'cuad_registros_diarios', 'r.cuadrillero_id', null],
                'plan_actividad_producciones' => ['plan_actividad_bonos', 'plan_registros_diarios', null, 'd.plan_empleado_id']] as $tabla => [$bonos, $registros, $colCuad, $colEmp]) {
                if (!Schema::hasTable($tabla)) {
                    continue;
                }
                $q = DB::table("{$tabla} as p")->join("{$bonos} as b", 'b.id', '=', 'p.actividad_bono_id')
                    ->join("{$registros} as r", 'r.id', '=', 'b.registro_diario_id')->join('actividades as a', 'a.id', '=', 'b.actividad_id');
                if ($colEmp) {
                    $q->join('plan_mensual_detalles as d', 'd.id', '=', 'r.plan_det_men_id');
                }
                $filas = $q->get(['a.id as actividad_id', 'a.codigo_labor', 'a.fecha', 'p.numero_recojo', 'p.produccion',
                    DB::raw(($colCuad ?? 'NULL') . ' as cuadrillero_id'), DB::raw(($colEmp ?? 'NULL') . ' as plan_empleado_id')]);
                foreach ($filas as $f) {
                    if ((float) $f->produccion == 0) {
                        continue;
                    }
                    $clave = ['actividad_id' => $f->actividad_id, 'cuadrillero_id' => $f->cuadrillero_id, 'plan_empleado_id' => $f->plan_empleado_id,
                        'numero_recojo' => $f->numero_recojo, 'con_bono' => true];
                    if (!DB::table('produccion_registros')->where($clave)->exists()) {
                        DB::table('produccion_registros')->insert($clave + ['cantidad' => $f->produccion,
                            'unidad' => CampoProduccionReglas::codigo(DB::table('labores')->where('codigo', $f->codigo_labor)->value('unidades')),
                            'created_at' => now(), 'updated_at' => now()]);
                        $copiadas++;
                    }
                }
            }
            $this->line("Cantidades copiadas de las tablas antiguas: {$copiadas}");

            // 3. Labores que registran producción
            $conCantidades = DB::table('produccion_registros as p')->join('actividades as a', 'a.id', '=', 'p.actividad_id')
                ->distinct()->pluck('a.codigo_labor')->all();
            $filas = [];
            foreach (DB::table('labores')->orderBy('codigo')->get() as $l) {
                $tieneTramos = filled($l->tramos_bonificacion) && $l->tramos_bonificacion !== '[]' && $l->tramos_bonificacion !== 'null';
                $registra = in_array($l->codigo, $conCantidades) || $l->estandar_produccion !== null || $tieneTramos;
                $unidad = $registra ? CampoProduccionReglas::codigo($l->unidades) : null;
                if ($registra && !$unidad) {
                    // Produce pero nunca se le puso unidad: la de sus actividades con cantidades, si la hay
                    $unidad = CampoProduccionReglas::codigo(DB::table('produccion_registros as p')->join('actividades as a', 'a.id', '=', 'p.actividad_id')
                        ->where('a.codigo_labor', $l->codigo)->whereNotNull('p.unidad')->value('p.unidad'));
                }
                if ((bool) ($l->registra_produccion ?? false) !== $registra || $l->unidades !== $unidad) {
                    $filas[] = [$l->codigo, $l->nombre_labor, $l->unidades ?? '—', $registra ? 'SI' : 'NO', $unidad ?? ($registra ? '¡FALTA!' : '—')];
                    DB::table('labores')->where('id', $l->id)->update(['registra_produccion' => $registra, 'unidades' => $unidad]);
                }
            }
            // Unidades de las vigencias al código del catálogo
            if (Schema::hasTable('labor_vigencias')) {
                foreach (DB::table('labor_vigencias')->whereNotNull('unidades')->get(['id', 'unidades']) as $v) {
                    DB::table('labor_vigencias')->where('id', $v->id)->update(['unidades' => CampoProduccionReglas::codigo($v->unidades)]);
                }
            }
            $this->table(['Código', 'Labor', 'Unidad antes', 'Registra', 'Unidad'], $filas);
            $faltan = array_filter($filas, fn($f) => $f[4] === '¡FALTA!');
            if ($faltan) {
                $this->warn(count($faltan) . ' labor(es) registran producción sin unidad: asígnala en Labores.');
            }

            $simular ? DB::rollBack() : DB::commit();
            $this->info($simular ? 'Simulación: no se guardó nada.' : 'Listo.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
