<?php

namespace App\Livewire;

use App\Models\CochinillaIngreso;
use Livewire\Component;
use Carbon\CarbonPeriod;

class CochinillaIngresoMapaComponent extends Component
{
    public $datosPorFecha = [];
    public $mostrarFormulario = false;
    public $ingresoFinal;
    public $resumen;
    protected $listeners = ["abrirMapa"];
    public function abrirMapa($ingresoId)
    {
        $this->reset(['ingresoFinal']);
        $registroIngreso = CochinillaIngreso::with(['venteados', 'filtrados', 'detalles', 'detalles.observacionRelacionada'])->find($ingresoId);

        if (!$registroIngreso) {
            $this->datosPorFecha = [];
            return;
        }
        $this->resumen = $registroIngreso;
        $this->ingresoFinal = $registroIngreso;
        $detalles = $registroIngreso->detalles->toBase();
        $venteados = $registroIngreso->venteados;
        $filtrados = $registroIngreso->filtrados;

        $detalles->push($registroIngreso);

        $minFecha = collect([
            $detalles->min('fecha'),
            $venteados->min('fecha_proceso'),
            $filtrados->min('fecha_proceso'),
        ])->filter()->min();

        $maxFecha = collect([
            $detalles->max('fecha'),
            $venteados->max('fecha_proceso'),
            $filtrados->max('fecha_proceso'),
        ])->filter()->max();

        $periodo = CarbonPeriod::create($minFecha, $maxFecha);
        $datosPorFecha = [];

        foreach ($periodo as $fecha) {
            $fechaStr = $fecha->format('Y-m-d');

            $datosPorFecha[$fechaStr] = [
                'ingresos' => $detalles->filter(fn($i) => $i->fecha === $fechaStr)->values(),
                'venteados' => $venteados->filter(fn($v) => $v->fecha_proceso === $fechaStr)->values(),
                'filtrados' => $filtrados->filter(fn($f) => $f->fecha_proceso === $fechaStr)->values(),
            ];
        }
        
        $this->datosPorFecha = $datosPorFecha;
        
        $this->dispatch('cargarDataMapaChart', [
            'total_kilos' => $this->resumen->total_kilos,
            'total_venteado_kilos_ingresados' => $this->resumen->total_venteado_kilos_ingresados ?? 0,
            'total_filtrado_kilos_ingresados' => $this->resumen->total_filtrado_kilos_ingresados ?? 0,
            'merma_ingreso_venteado' => $this->resumen->merma_ingreso_venteado ?? 0,
            'merma_venteado_filtrado' => $this->resumen->merma_venteado_filtrado ?? 0,
            'merma_ingreso_filtrado' => $this->resumen->merma_ingreso_filtrado ?? 0,
            'material_util_venteado' => $this->resumen->material_util_venteado ?? 0,
            'material_util_filtrado' => $this->resumen->material_util_filtrado ?? 0,
        ]);
        $this->mostrarFormulario = true;
    }
    public function calcularTamanioPx($valor, $maximo, $maxPx = 100, $minPx = 10)
    {
        if ($valor <= 0)
            return 0;

        if ($maximo == 0)
            return $minPx;

        $proporcion = $valor / $maximo;
        $tamano = round($proporcion * $maxPx);

        return max($tamano, $minPx);
    }



    public function render()
    {
        return view('livewire.cochinilla_ingreso_mapa_component.index');
    }
}
