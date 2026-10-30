<?php

namespace App\Livewire\Cuadrilla;
use App\Services\Cuadrilla\Bono\CuadrillaBonoProceso;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class GestionCuadrillaAsignacionCostosComponent extends Component
{
    use LivewireAlert, \App\Traits\HandlesAlerts;
    public $fechaInicio;
    public $fechaFin;
    public $costosAsignados = [];
    public $totalDias = 0;
    public $headers = [];
    public $mostrarFormularioAignacionCostos = false;
    public $tramoLaboral;
    public $tramoId;
    /** Cambio de jornal que cambia bonos "respeta el jornal": se confirma antes de guardar */
    public bool $mostrarConfirmacionBonos = false;
    public array $bonosAfectados = [];
    public ?array $datosPendientes = null;
    protected $listeners = ['asignarCostosPorFecha'];
    public function mount()
    {

    }
    public function reInicializar($tramoId){
        $this->tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoId);
        if($this->tramoLaboral){
           $this->fechaInicio = $this->tramoLaboral->fecha_inicio;
           $this->fechaFin = $this->tramoLaboral->fecha_fin;
        }
        $this->obtenerCostosAsignados();
    }
    public function asignarCostosPorFecha($tramoId)
    {
        $this->mostrarFormularioAignacionCostos = true;
        $this->reInicializar($tramoId);
    }
    public function obtenerCostosAsignados()
    {
        $resultado = CuadrilleroServicio::obtenerHandsontableCostosAsignados($this->tramoLaboral->id);
        $this->costosAsignados = $resultado['data'];
        $this->totalDias = $resultado['total_dias'];
        $this->headers = $resultado['headers'];
        $this->dispatch('actualizarTablaAsignacionCosto', $this->costosAsignados, $this->totalDias, $this->headers);
    }
    public function guardarAsignacionCostos($datos)
    {
        try {
            if (!$this->fechaInicio) {
                $hoy = Carbon::today();
                $this->fechaInicio = $hoy->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            }
            // Si el nuevo jornal cambia bonos "respeta el jornal", primero se muestran para confirmar
            [$desde, $hasta] = $this->rango($datos);
            $afectados = CuadrillaBonoProceso::simular(fn() => $this->guardar($datos), $desde, $hasta);
            if ($afectados) {
                $this->datosPendientes = $datos;
                $this->bonosAfectados = $afectados;
                $this->mostrarConfirmacionBonos = true;
                return;
            }
            $this->guardar($datos);
            $this->terminar('Costos asignados guardados correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    /** Confirmado: guarda el jornal y actualiza los bonos que dependen de él (los pagados no se tocan). */
    public function confirmarCambioJornal(): void
    {
        try {
            if (!$this->datosPendientes) {
                return;
            }
            $datos = $this->datosPendientes;
            [$desde, $hasta] = $this->rango($datos);
            $n = DB::transaction(function () use ($datos, $desde, $hasta) {
                $this->guardar($datos);
                return CuadrillaBonoProceso::aplicar($desde, $hasta);
            });
            $this->cancelarCambioJornal();
            $this->terminar("Costos asignados guardados. Se actualizaron {$n} bono(s).");
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }

    public function cancelarCambioJornal(): void
    {
        $this->reset(['mostrarConfirmacionBonos', 'bonosAfectados', 'datosPendientes']);
    }

    private function guardar(array $datos): void
    {
        CuadrilleroServicio::guardarCostosDiariosGrupo($datos, $this->fechaInicio);
        CuadrilleroServicio::guardarPrecioSugerido($datos);
    }

    /** Fechas que cubre la tabla de costos (una columna dia_N por día desde fechaInicio). */
    private function rango(array $datos): array
    {
        $dias = 1;
        foreach ($datos as $grupo) {
            $dias = max($dias, count(array_filter(array_keys((array) $grupo), fn($k) => str_starts_with((string) $k, 'dia_'))));
        }
        $inicio = Carbon::parse($this->fechaInicio);
        return [$inicio->toDateString(), $inicio->copy()->addDays($dias - 1)->toDateString()];
    }

    private function terminar(string $mensaje): void
    {
        $this->alert('success', $mensaje);
        $this->mostrarFormularioAignacionCostos = false;
        $this->dispatch('costosSemanalesModificados');
    }

    public function render()
    {
        return view('livewire.cuadrilla.gestion-cuadrilla-asignacion-costos-component');
    }
}
