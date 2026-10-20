<?php

namespace App\Services\Costos\ManoObraIndirecta;

use App\Models\AlmacenProductoSalida;
use App\Models\CostoMensual;
use App\Services\Almacen\Kardex\KardexActualizacionServicio;
use App\Services\Costos\Consolidacion\BddManoObraServicio;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calcula y guarda la mano de obra indirecta del mes:
 * 1) pone al día la BDD de mano de obra del mes (planilla, cuadrilla, riego, bonos, indirecta) y los kardex de las
 *    salidas de combustible e insumos a FDM (si un kardex no se puede regenerar queda como aviso, no detiene);
 * 2) calcula (CostosManoObraIndirectaConsulta) y genera el Excel;
 * 3) reemplaza operativo_mano_obra_indirecta_blanco/negro de costos_mensuales, con quién, cuándo, el Excel, si fue
 *    concluyente y el resumen por sección; queda en auditoría.
 * Se vuelve a ejecutar cuando se corrige planilla, kardex o distribuciones ("Recalcular").
 */
class CostosManoObraIndirectaProceso
{
    public function __construct(
        private BddManoObraServicio $bdd,
        private KardexActualizacionServicio $kardex,
        private CostosManoObraIndirectaConsulta $consulta,
        private CostosManoObraIndirectaExcel $excel,
    ) {
    }

    public function calcular(int $anio, int $mes): array
    {
        $inicio = Carbon::create($anio, $mes, 1)->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $this->bdd->asegurarMes($anio, $mes);
        $salidas = AlmacenProductoSalida::whereBetween('fecha_reporte', [$inicio, $fin])
            ->where(fn($q) => $q->whereNotNull('maquinaria_id')->orWhereRaw('UPPER(campo_nombre) = ?', [CostosManoObraIndirectaReglas::CAMPO_FDM]))
            ->whereNotNull('tipo_kardex')->get();
        try {
            $this->kardex->asegurarActualizados($salidas);
        } catch (\Throwable) {
            // Los kardex que no se pudieron regenerar salen como aviso "no concluyente" en el cálculo
        }

        $calculo = $this->consulta->calcular($anio, $mes);
        $usuario = auth()->user();
        $archivo = $this->excel->guardar($calculo, $usuario?->name);

        DB::transaction(function () use ($anio, $mes, $calculo, $archivo, $usuario) {
            $costo = CostoMensual::firstOrNew(['anio' => $anio, 'mes' => $mes]);
            $antes = $costo->exists ? $costo->only(['operativo_mano_obra_indirecta_blanco', 'operativo_mano_obra_indirecta_negro', 'moi_concluyente']) : null;
            $costo->fill([
                'operativo_mano_obra_indirecta_blanco' => $calculo['blanco'],
                'operativo_mano_obra_indirecta_negro' => $calculo['negro'],
                'moi_calculado_en' => now(),
                'moi_calculado_por' => $usuario?->id,
                'moi_archivo' => $archivo,
                'moi_concluyente' => $calculo['concluyente'],
                'moi_resumen' => [
                    'secciones' => collect($calculo['secciones'])->map(fn($s) => [
                        'titulo' => $s['titulo'], 'blanco' => $s['blanco'], 'negro' => $s['negro'], 'total' => $s['total'],
                    ])->all(),
                    'avisos' => $calculo['avisos'],
                ],
            ])->save();

            AuditoriaServicio::registrar(
                modelo: CostoMensual::class,
                modeloId: $costo->id,
                accion: $antes ? 'editar' : 'crear',
                antes: $antes,
                despues: $costo->only(['operativo_mano_obra_indirecta_blanco', 'operativo_mano_obra_indirecta_negro', 'moi_concluyente']),
                observacion: "Mano de obra indirecta {$mes}/{$anio} calculada: S/ " . number_format($calculo['total'], 2)
                    . ($calculo['concluyente'] ? '' : ' (no concluyente)'),
            );
        });

        return $calculo + ['archivo' => $archivo];
    }
}
