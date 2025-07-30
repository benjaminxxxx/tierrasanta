<?php

namespace App\Livewire\GestionCuadrilla;

use App\Models\CostoManoIndirecta;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Session;

class GestionCuadrillaResumenAnualComponent extends Component
{
    use LivewireAlert;
    public $aniosDisponibles = [];
    public $totalCostoAnual = 0;
    public $totalBonoAnual = 0;
    public $totalSumadoAnual = 0;
    public $anioSeleccionado = null;
    public $resumenMensual = [];
    public $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    public function mount()
    {
        $this->aniosDisponibles = range(2017, now()->year);
        $this->anioSeleccionado = Session::get('anio', now()->year);
        $this->obtenerResumenMensual();
    }
    public function obtenerResumenMensual()
    {
        if (!$this->anioSeleccionado)
            return;

        try {
            $registros = CostoManoIndirecta::where('anio', $this->anioSeleccionado)
                ->get()
                ->keyBy('mes'); // Agrupa por mes para acceso rápido

            $this->resumenMensual = [];

            // Reiniciar totales
            $this->totalCostoAnual = 0;
            $this->totalBonoAnual = 0;
            $this->totalSumadoAnual = 0;

            for ($i = 1; $i <= 12; $i++) {
                $resumen = $registros->get($i);

                if ($resumen) {
                    $totalCosto = $resumen->negro_cuadrillero_monto - $resumen->negro_cuadrillero_bono;
                    $totalBono = $resumen->negro_cuadrillero_bono;
                    $totalSumado = $resumen->negro_cuadrillero_monto;

                    $this->resumenMensual[] = [
                        'totalCosto' => $totalCosto,
                        'totalBono' => $totalBono,
                        'totalSumado' => $totalSumado,
                        'reporteMes' => $resumen->negro_cuadrillero_file ?? null,
                    ];

                    $this->totalCostoAnual += $totalCosto;
                    $this->totalBonoAnual += $totalBono;
                    $this->totalSumadoAnual += $totalSumado;

                } else {
                    $this->resumenMensual[] = [
                        'totalCosto' => 0,
                        'totalBono' => 0,
                        'totalSumado' => 0,
                        'reporteMes' => null,
                    ];
                }
            }

            $this->dispatch('actualizarGraficoCuadrilla', $this->resumenMensual);

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function updatedAnioSeleccionado()
    {
        $this->obtenerResumenMensual();
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-resumen-anual-component');
    }
}
