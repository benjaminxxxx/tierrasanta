<?php

namespace App\Livewire\Reporte;

use App\Models\CostoMensual;
use App\Services\Costos\ManoObraIndirecta\CostosManoObraIndirectaConsulta;
use Livewire\Component;

/**
 * Box del reporte mensual: mano de obra indirecta (todo lo gastado en FDM) del mes. Muestra lo guardado en el costo
 * mensual; si el mes no se calculó, una vista previa con los datos de hoy (sin guardar).
 */
class ReporteMensualManoObraIndirectaComponent extends Component
{
    public int $mes;
    public int $anio;

    public function render()
    {
        $guardado = CostoMensual::with('moiCalculadoPor:id,name')->where('anio', $this->anio)->where('mes', $this->mes)->first();
        if ($guardado?->moi_calculado_en) {
            $datos = [
                'origen' => 'guardado',
                'blanco' => (float) $guardado->operativo_mano_obra_indirecta_blanco,
                'negro' => (float) $guardado->operativo_mano_obra_indirecta_negro,
                'concluyente' => (bool) $guardado->moi_concluyente,
                'secciones' => $guardado->moi_resumen['secciones'] ?? [],
                'avisos' => $guardado->moi_resumen['avisos'] ?? [],
            ];
        } else {
            $c = app(CostosManoObraIndirectaConsulta::class)->calcular($this->anio, $this->mes);
            $datos = [
                'origen' => 'vista_previa',
                'blanco' => $c['blanco'],
                'negro' => $c['negro'],
                'concluyente' => $c['concluyente'],
                'secciones' => array_values(array_map(fn($s) => array_intersect_key($s, array_flip(['titulo', 'blanco', 'negro', 'total'])), $c['secciones'])),
                'avisos' => $c['avisos'],
            ];
        }
        $datos['total'] = round($datos['blanco'] + $datos['negro'], 2);

        return view('livewire.reporte.reporte-mensual-mano-obra-indirecta-component', compact('datos', 'guardado'));
    }
}
