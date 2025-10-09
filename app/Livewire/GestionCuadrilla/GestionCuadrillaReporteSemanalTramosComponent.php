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

    /**
     * Hook que se ejecuta cuando se actualiza la propiedad $fecha_inicio y fecha_fin.
     */
    public function updatedFechaInicio($valor): void
    {
        if (empty($valor)) {
            $this->reset(['titulo']);
            return;
        }

        if ($this->fecha_fin) {
            $this->titulo = $this->tramoLaboralServicio->generarTitulo(Carbon::parse($valor), Carbon::parse($this->fecha_fin));
        }
    }
    public function updatedFechaFin($valor): void
    {
        if (empty($valor)) {
            $this->reset(['titulo']);
            return;
        }

        if ($this->fecha_inicio) {
            $this->titulo = $this->tramoLaboralServicio->generarTitulo( Carbon::parse($this->fecha_inicio),Carbon::parse($valor));
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
