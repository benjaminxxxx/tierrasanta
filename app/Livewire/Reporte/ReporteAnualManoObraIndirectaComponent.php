<?php

namespace App\Livewire\Reporte;

use App\Models\CostoMensual;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Box del reporte anual: mano de obra indirecta (todo lo gastado en FDM) de cada mes del año, con lo guardado en el
 * costo mensual. Los meses sin calcular se muestran como tales (calcular los 12 en vivo tardaría demasiado).
 */
class ReporteAnualManoObraIndirectaComponent extends Component
{
    public int $anio;

    public function render()
    {
        $guardados = CostoMensual::where('anio', $this->anio)->get()->keyBy('mes');
        $hoy = Carbon::today();
        $meses = collect(range(1, 12))->map(function ($m) use ($guardados, $hoy) {
            $c = $guardados->get($m);
            $inicio = Carbon::create($this->anio, $m, 1);
            $blanco = (float) ($c?->operativo_mano_obra_indirecta_blanco ?? 0);
            $negro = (float) ($c?->operativo_mano_obra_indirecta_negro ?? 0);
            return [
                'mes' => $m,
                'nombre' => ucfirst($inicio->translatedFormat('F')),
                'blanco' => $blanco,
                'negro' => $negro,
                'total' => $blanco + $negro,
                'estado' => match (true) {
                    $inicio->gt($hoy) => 'futuro',
                    (bool) $c?->moi_calculado_en => $c->moi_concluyente ? 'concluyente' : 'no_concluyente',
                    $blanco + $negro > 0 => 'manual',
                    default => 'sin_calcular',
                },
            ];
        });

        return view('livewire.reporte.reporte-anual-mano-obra-indirecta-component', [
            'meses' => $meses,
            'blanco' => $meses->sum('blanco'),
            'negro' => $meses->sum('negro'),
            'total' => $meses->sum('total'),
            'maximo' => max(1, $meses->max('total')),
            'pendientes' => $meses->whereIn('estado', ['sin_calcular', 'no_concluyente', 'manual'])
                ->filter(fn($m) => Carbon::create($this->anio, $m['mes'], 1)->endOfMonth()->lt($hoy))->count(),
        ]);
    }
}
