<?php

namespace App\Livewire\GestionCuadrilla;

use App\Services\Cuadrilla\TramoLaboralServicio;
use Carbon\Carbon;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class GestionCuadrillaReporteSemanalTramosComponent extends Component
{
    use LivewireAlert;

    // Propiedades de estado de la UI
    public bool $mostrarFormularioReporteSemanalTramo = false;
    public bool $mostrarBuscadorDeTramos = false;
    public ?int $cambios = 1;

    // Propiedades para el formulario
    public ?string $titulo = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?bool $acumula_costos = true;
    public ?int $tramoId = null;

    // Modelos de Eloquent
    public $tramoActual;
    public $tramoAnterior;
    public $tramoSiguiente;
    public $filtroBuscarTramo = [];
    public $resultadoBuquedaTramos = [];

    // Servicio
    protected $listeners = ['eliminarTramo', 'confirmadoEliminarTramo', 'editarTramo'];
    private TramoLaboralServicio $tramoLaboralServicio;

    /**
     * Inyecta el servicio al inicializar el componente.
     */
    public function boot(TramoLaboralServicio $tramoLaboralServicio)
    {
        $this->tramoLaboralServicio = $tramoLaboralServicio;
    }

    /**
     * Carga los datos iniciales al montar el componente.
     */
    public function mount(): void
    {

        $this->cargarDatosActuales();

    }

    public function buscarTramo()
    {
        $this->filtroBuscarTramo = [
            'mes' => now()->month,
            'anio' => now()->year
        ];
        $this->mostrarBuscadorDeTramos = true;
        $this->ejecutarBusqueda();
    }

    // Se ejecuta automáticamente cuando cambian los selects
    public function updatedFiltroBuscarTramo()
    {
        $this->ejecutarBusqueda();
    }

    public function mesAnterior()
    {
        $this->navegarMes(-1);
    }

    public function mesSiguiente()
    {
        $this->navegarMes(1);
    }

    private function navegarMes($cambio)
    {
        // Creamos una fecha Carbon con los valores actuales
        $fecha = Carbon::createFromDate(
            $this->filtroBuscarTramo['anio'],
            $this->filtroBuscarTramo['mes'],
            1
        );

        // Sumamos o restamos el mes (Carbon maneja el cambio de año automáticamente)
        $fecha->addMonths($cambio);

        // Actualizamos los filtros
        $this->filtroBuscarTramo['mes'] = $fecha->month;
        $this->filtroBuscarTramo['anio'] = $fecha->year;

        $this->ejecutarBusqueda();
    }

    private function ejecutarBusqueda()
    {
        try {
            $mes = $this->filtroBuscarTramo['mes'] ?? null;
            $anio = $this->filtroBuscarTramo['anio'] ?? null;

            // Validación robusta antes de consultar
            if ($mes >= 1 && $mes <= 12 && strlen($anio) === 4) {
                $this->resultadoBuquedaTramos = $this->tramoLaboralServicio
                    ->encontrarTramoPorMesAnioLista($mes, $anio);
            }
        } catch (\Throwable $th) {
            $this->alert('error', 'Error en búsqueda: ' . $th->getMessage());
        }
    }
    public function seleccionarTramo($tramoLaboralId)
    {
        $this->tramoActual = $this->tramoLaboralServicio->encontrarTramoPorId($tramoLaboralId);
        $this->mostrarBuscadorDeTramos = false;
        if ($this->tramoActual) {
            session()->put('tramo_actual_id', $this->tramoActual->id);
            $this->tramoAnterior = $this->tramoLaboralServicio->encontrarAnterior($this->tramoActual);
            $this->tramoSiguiente = $this->tramoLaboralServicio->encontrarSiguiente($this->tramoActual);
        } else {
            $this->tramoAnterior = null;
            $this->tramoSiguiente = null;
        }
    }
    /**
     * Guarda un tramo (crea o actualiza).
     */
    public function guardarTramoSemanal(): void
    {
        $this->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'titulo' => 'required|string|max:255',
        ]);

        try {
            $datos = [
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin' => $this->fecha_fin,
                'titulo' => $this->titulo,
                'acumula_costos' => $this->acumula_costos,
            ];

            if ($this->tramoId) {
                $this->tramoLaboralServicio->actualizar($this->tramoId, $datos);
                $mensaje = 'Tramo semanal actualizado correctamente.';
            } else {
                $this->tramoLaboralServicio->crear($datos);
                $mensaje = 'Tramo semanal registrado correctamente.';
            }

            $this->alert('success', $mensaje);
            $this->cerrarFormularioYRecargar();

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function confirmadoEliminarTramo()
    {
        try {
            if (!$this->tramoActual) {
                throw new \Exception("No hay tramo seleccionado para eliminar.");
            }

            $this->tramoLaboralServicio->eliminar($this->tramoActual);

            $this->alert('success', 'Tramo eliminado correctamente.');
            $this->cargarDatosActuales();

        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    /**
     * Elimina el tramo actual.
     */
    public function eliminarTramo(): void
    {
        $this->confirm('¿Está seguro de eliminar este tramo?', [
            'onConfirmed' => 'confirmadoEliminarTramo'
        ]);
    }

    /**
     * Navega al tramo anterior.
     */
    public function irTramoAnterior(): void
    {
        if ($this->tramoAnterior) {
            $this->tramoActual = $this->tramoAnterior;
            $this->actualizarSesionYRecargar();
        }
    }

    /**
     * Navega al tramo siguiente.
     */
    public function irTramoSiguiente(): void
    {
        if ($this->tramoSiguiente) {
            $this->tramoActual = $this->tramoSiguiente;
            $this->actualizarSesionYRecargar();
        }
    }

    /**
     * Prepara el formulario para crear un nuevo tramo.
     */
    public function crearNuevoTramo(): void
    {
        $this->reset(['fecha_inicio', 'fecha_fin', 'titulo', 'tramoId']);
        $this->acumula_costos = true;
        $this->mostrarFormularioReporteSemanalTramo = true;
    }

    /**
     * Prepara el formulario para editar el tramo actual.
     */
    public function editarTramo(): void
    {
        if (!$this->tramoActual) {
            $this->alert('error', 'No hay tramo seleccionado para editar.');
            return;
        }

        $this->tramoId = $this->tramoActual->id;
        $this->fecha_inicio = $this->tramoActual->fecha_inicio;
        $this->fecha_fin = $this->tramoActual->fecha_fin;
        $this->acumula_costos = $this->tramoActual->acumula_costos;
        $this->titulo = $this->tramoActual->titulo;
        $this->mostrarFormularioReporteSemanalTramo = true;
    }

    /**
     * Carga el tramo actual y sus vecinos (anterior y siguiente).
     */
    private function cargarDatosActuales()
    {
        $this->tramoActual = $this->tramoLaboralServicio->encontrarActual();

        if ($this->tramoActual) {
            $this->tramoAnterior = $this->tramoLaboralServicio->encontrarAnterior($this->tramoActual);
            $this->tramoSiguiente = $this->tramoLaboralServicio->encontrarSiguiente($this->tramoActual);
        } else {
            $this->tramoAnterior = null;
            $this->tramoSiguiente = null;
        }
    }

    /**
     * Actualiza la sesión con el ID del tramo actual y recarga los datos.
     */
    private function actualizarSesionYRecargar(): void
    {
        session()->put('tramo_actual_id', $this->tramoActual->id);
        $this->cargarDatosActuales();
    }

    /**
     * Cierra el formulario, resetea su estado y recarga los datos de los tramos.
     */
    private function cerrarFormularioYRecargar()
    {
        $this->mostrarFormularioReporteSemanalTramo = false;
        $this->reset(['fecha_inicio', 'fecha_fin', 'titulo', 'tramoId']);
        $this->acumula_costos = true;
        $this->cargarDatosActuales();
        $this->cambios++;
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-tramos-component');
    }
}
