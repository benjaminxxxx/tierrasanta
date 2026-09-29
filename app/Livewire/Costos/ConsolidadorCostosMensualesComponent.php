<?php

namespace App\Livewire\Costos;

use App\Models\CostoMensual;
use App\Services\Costos\Consolidacion\ConsolidarCostoInsumosServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoManoObraServicio;
use App\Services\Costos\Consolidacion\ConsolidarCostoServiciosCampoServicio;
use App\Services\Costos\Consolidacion\ConsolidarReporteMensualCostos;
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
    /**
     * Consolida el mes. La mano de obra ya la mantiene al día la BDD al guardar cada registro:
     * aquí solo se rehacen los días que cambiaron después ($reconstruir = rehacer todo el mes).
     */
    public function generarMes(int $mesNum, bool $reconstruir = false): void
    {
        try {
            if (!$mesNum || !$this->anio) {
                throw new \Exception("Parámetros de mes y año inválidos.");
            }
            $fechaInicio = Carbon::create($this->anio, $mesNum, 1)->startOfMonth()->format('Y-m-d');
            $fechaFin = Carbon::create($this->anio, $mesNum, 1)->endOfMonth()->format('Y-m-d');

            // Mano de obra (planilla, cuadrilla, riego, bonos) + mano de obra indirecta
            $manoObra = app(\App\Services\Costos\Consolidacion\BddManoObraServicio::class)
                ->asegurarMes($this->anio, $mesNum, $reconstruir);
            $manoObraIndirecta = ['avisos' => $manoObra['avisos']];

            $totalServicios = app(ConsolidarCostoServiciosCampoServicio::class)
                ->consolidarEnRango($fechaInicio, $fechaFin);

            // Actualiza los kardex desactualizados antes de leer el costo de las salidas;
            // si alguno no se puede actualizar, lanza el error y no se genera el reporte.
            $totalInsumos = app(ConsolidarCostoInsumosServicio::class)
                ->consolidarEnRango($fechaInicio, $fechaFin);

            app(ConsolidarReporteMensualCostos::class)
                ->ejecutar($this->anio, $mesNum);

            $mensaje = "Mes {$mesNum}/{$this->anio} consolidado correctamente ("
                . ($reconstruir ? 'mano de obra reconstruida' : "mano de obra: {$manoObra['dias']} día(s) actualizados")
                . ", {$totalServicios} filas de servicios en campo, {$totalInsumos} de fertilizantes/pesticidas).";
            if ($manoObraIndirecta['avisos']) {
                // Vacaciones registradas sin monto pagado: su costo no entra al cuadre
                $mensaje .= ' Revisar: ' . implode(' ', $manoObraIndirecta['avisos']);
                $this->alert('warning', $mensaje, ['toast' => false, 'position' => 'center', 'timer' => null, 'showConfirmButton' => true]);
            } else {
                $this->alert('success', $mensaje);
            }
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