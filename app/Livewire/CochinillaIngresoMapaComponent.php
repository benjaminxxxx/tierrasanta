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

        $this->resumen = [
            'total_kilos' => $registroIngreso->total_kilos,
            'venteado_total_kilos' => $registroIngreso->total_venteado_total,
            'total_venteado_limpia' => $registroIngreso->total_venteado_limpia,
            'total_venteado_basura' => $registroIngreso->total_venteado_basura,
            'total_venteado_polvillo' => $registroIngreso->total_venteado_polvillo,

            'filtrado_total_kilos' => $registroIngreso->total_filtrado_total,
            'total_filtrado_primera' => $registroIngreso->total_filtrado_primera,
            'total_filtrado_segunda' => $registroIngreso->total_filtrado_segunda,
            'total_filtrado_tercera' => $registroIngreso->total_filtrado_tercera,
            'total_filtrado_piedra' => $registroIngreso->total_filtrado_piedra,
            'total_filtrado_basura' => $registroIngreso->total_filtrado_basura,
        ];

        $valores = [
            $registroIngreso->total_kilos,
            $registroIngreso->total_venteado_total,
            $registroIngreso->total_venteado_limpia,
            $registroIngreso->total_venteado_basura,
            $registroIngreso->total_venteado_polvillo,
            $registroIngreso->total_filtrado_total,
            $registroIngreso->total_filtrado_primera,
            $registroIngreso->total_filtrado_segunda,
            $registroIngreso->total_filtrado_tercera,
            $registroIngreso->total_filtrado_piedra,
            $registroIngreso->total_filtrado_basura,
        ];

        $maximo = max($valores);
        $this->resumen['px_total_kilos'] = $this->calcularTamanioPx($registroIngreso->total_kilos, $maximo);

        $this->resumen['px_total_venteado'] = $this->calcularTamanioPx($registroIngreso->total_venteado_total, $maximo);
        $this->resumen['px_limpia'] = $this->calcularTamanioPx($registroIngreso->total_venteado_limpia, $maximo);
        $this->resumen['px_basura'] = $this->calcularTamanioPx($registroIngreso->total_venteado_basura, $maximo);
        $this->resumen['px_polvillo'] = $this->calcularTamanioPx($registroIngreso->total_venteado_polvillo, $maximo);

        $this->resumen['px_total_filtrado'] = $this->calcularTamanioPx($registroIngreso->total_filtrado_total, $maximo);
        $this->resumen['px_1ra'] = $this->calcularTamanioPx($registroIngreso->total_filtrado_primera, $maximo);
        $this->resumen['px_2da'] = $this->calcularTamanioPx($registroIngreso->total_filtrado_segunda, $maximo);
        $this->resumen['px_3ra'] = $this->calcularTamanioPx($registroIngreso->total_filtrado_tercera, $maximo);
        $this->resumen['px_filtrado_piedra'] = $this->calcularTamanioPx($registroIngreso->total_filtrado_piedra, $maximo);
        $this->resumen['px_filtrado_basura'] = $this->calcularTamanioPx($registroIngreso->total_filtrado_basura, $maximo);

        $this->datosPorFecha = $datosPorFecha;
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
        return view('livewire.cochinilla-ingreso-mapa-component');
    }
}
