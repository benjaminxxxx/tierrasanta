<?php

namespace App\Livewire\Costos;

use App\Models\CostoMensual;
use App\Services\Campo\Costos\ConsolidarCostoManoObraServicio;
use App\Services\Campo\Costos\ConsolidarReporteMensualCostos;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ConsolidadorCostosMensualesComponent extends Component
{
    use HandlesAlerts, LivewireAlert;

    public bool $mostrarModal = false;
    public ?int $anio = null;

    protected $listeners = [
        'abrirConsolidadorCostosMensuales' => 'abrirModal',
    ];

    public function mount(): void
    {
        $this->anio = (int) date('Y');
    }

    public function abrirModal(): void
    {
        $this->mostrarModal = true;
    }

    public function cerrarModal(): void
    {
        $this->mostrarModal = false;
    }

    /**
     * Procesa la consolidación para un mes específico.
     */
    public function generarMes(int $mesNum): void
    {
        try {
            if (!$mesNum || !$this->anio) {
                throw new \Exception("Parámetros de mes y año inválidos.");
            }
            $fechaInicio = Carbon::create($this->anio, $mesNum, 1)->startOfMonth()->format('Y-m-d');
            $fechaFin = Carbon::create($this->anio, $mesNum, 1)->endOfMonth()->format('Y-m-d');
            $totalPlanilla = 0;
/*
            $totalPlanilla = app(ConsolidarCostoManoObraServicio::class)
                ->consolidarPlanillaEnRango($fechaInicio, $fechaFin);
*/
            app(ConsolidarReporteMensualCostos::class)
                ->ejecutar($this->anio, $mesNum);

            $this->alert('success', "Mes {$mesNum}/{$this->anio} consolidado correctamente ({$totalPlanilla} filas procesadas).");
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function render()
    {
        // Cargar registros existentes del año seleccionado indexados por mes (1 a 12)
        $costosAnio = CostoMensual::where('anio', $this->anio)
            ->get()
            ->keyBy('mes');

        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return view('livewire.costos.consolidador-costos-mensuales-component', [
            'costosAnio'   => $costosAnio,
            'mesesNombres' => $mesesNombres,
        ]);
    }
}