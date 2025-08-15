<?php

namespace App\Livewire;

use App\Models\Cargo;
use App\Models\Contrato;
use App\Models\DescuentoSP;
use App\Models\Grupo;
use App\Services\RecursosHumanos\Personal\ContratoServicio;
use Carbon\Carbon;
use Exception;
use Livewire\Component;
use App\Models\Empleado;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithPagination;

class EmpleadosComponent extends Component
{
    use LivewireAlert;
    use WithPagination;
    public $isFormOpen = true;
    public $empleadoCode;
    public $search = '';
    public $cargo_id;
    public $descuento_sp_id;
    public $grupo_codigo;
    public $cargos;
    public $descuentos;
    public $grupos;
    public $estado;
    public $genero;
    public $tipo_planilla;
    public $mostrarFormularioCambioSueldos = false;
    public $trabajadoresActivos = [];
    public $mesVigencia;
    public $anioVigencia;
    protected $listeners = ['EmpleadoRegistrado' => '$refresh', 'eliminacionConfirmada', 'HijoRegistrado' => '$refresh'];
    public function mount()
    {
        $this->cargos = Cargo::all();
        $this->descuentos = DescuentoSP::all();
        $this->grupos = Grupo::all();
        $this->estado = 'activo';
        $this->mesVigencia = Carbon::now()->format('m');
        $this->anioVigencia = Carbon::now()->format('Y');
        //funcion momentanea mientras falen hacer contratos
        $this->generarContratos();
    }
    public function generarContratos()
    {
        $empleados = Empleado::whereDoesntHave('contratos')->get();
        if ($empleados->count() == 0) {
            return;
        }
        foreach ($empleados as $empleado) {
            $fecha_inicio = $empleado->fecha_ingreso ?? '2016-01-01';
            Contrato::create([
                'empleado_id' => $empleado->id,
                'tipo_contrato' => 'indefinido',
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => null,
                'sueldo' => $empleado->salario,
                'cargo_codigo' => $empleado->cargo_id,
                'grupo_codigo' => $empleado->grupo_codigo,
                'compensacion_vacacional' => $empleado->compensacion_vacacional,
                'tipo_planilla' => $empleado->tipo_planilla,
                'descuento_sp_id' => $empleado->descuento_sp_id,
                'esta_jubilado' => $empleado->esta_jubilado,
                'modalidad_pago' => 'mensual',
                'motivo_despido' => null
            ]);
        }

    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function editar($code)
    {
        $this->dispatch('EditarEmpleado', $code);
    }
    public function asignacionFamiliar($code)
    {
        $this->dispatch('AgregarAsignacionFamiliar', $code);
    }
    public function eliminacionConfirmada()
    {
        if ($this->empleadoCode) {
            $empleado = Empleado::where('code', $this->empleadoCode);
            if ($empleado) {
                $empleado->delete();
                $this->empleadoCode = null;
            }
        }
    }
    /*
    public function enable($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'activo';
            $empleado->save();
        }
    }

    public function disable($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'inactivo';
            $empleado->save();
        }
    }*/
    public function restaurar($code)
    {
        $empleado = Empleado::where('code', $code)->first();
        if ($empleado) {
            $empleado->status = 'activo';
            $empleado->save();
        }
    }
    public function confirmarEliminacion($code)
    {
        try {
            $empleado = Empleado::where('code', $code)->firstOrFail();
            $empleado->status = 'inactivo';
            $empleado->save();
            $this->alert('success', 'Registro eliminado correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function moveUp($id)
    {
        $empleado = Empleado::find($id);

        if ($empleado) {
            // Verificar si el empleado tiene un valor de 'orden' NULL
            if (is_null($empleado->orden)) {
                // Asignar valores al campo 'orden' si son NULL
                $this->assignOrderValues();
            } else {
                // Mover el empleado hacia arriba si ya tiene un valor de 'orden'
                $previous = Empleado::where('orden', '<', $empleado->orden)
                    ->orderBy('orden', 'desc')
                    ->where('status', 'activo')
                    ->first();

                if ($previous) {
                    $this->swapOrder($empleado, $previous);
                }
            }
        }
    }

    public function moveDown($id)
    {
        $empleado = Empleado::find($id);

        if ($empleado) {
            // Verificar si el empleado tiene un valor de 'orden' NULL
            if (is_null($empleado->orden)) {
                // Asignar valores al campo 'orden' si son NULL
                $this->assignOrderValues();
            } else {
                // Mover el empleado hacia abajo si ya tiene un valor de 'orden'
                $next = Empleado::where('orden', '>', $empleado->orden)
                    ->orderBy('orden', 'asc')
                    ->where('status', 'activo')
                    ->first();

                if ($next) {
                    $this->swapOrder($empleado, $next);
                }
            }
        }
    }
    public function moveAt($id, $value)
    {
        $empleado = Empleado::find($id);

        if ($empleado) {
            // Asignar el valor al campo 'orden'
            $empleado->orden = $value;
            $empleado->save(); // Guardar los cambios en la base de datos
        }
    }
    private function assignOrderValues()
    {
        // Inicializar el valor de orden
        $empleados = Empleado::orderBy('id')->where('status', 'activo')->get();
        $order = 1;

        foreach ($empleados as $empleado) {
            // Solo actualizar los empleados con 'orden' NULL
            if (is_null($empleado->orden)) {
                $empleado->orden = $order++;
                $empleado->save();
            }
        }
    }

    private function swapOrder($current, $target)
    {
        $tempOrder = $current->orden;
        $current->orden = $target->orden;
        $target->orden = $tempOrder;

        $current->save();
        $target->save();

    }

    #region aumento de sueldo
    public function abrirFormCambioMasivoSueldo()
    {
        $lista = Empleado::where('status', 'activo')
            ->with(['ultimoContrato'])
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'nombre' => trim("{$e->nombres} {$e->apellido_paterno} {$e->apellido_materno}"),
                    'grupo_codigo' => $e->grupo_codigo,
                    'cargo_codigo' => $e->cargo_id, // o cargo_codigo si lo tienes así
                    'tipo_planilla' => (string) $e->tipo_planilla, // "1" o "2"
                    'sueldo_actual' => optional($e->ultimoContrato)->sueldo ?? 0,
                    'nuevo_sueldo' => optional($e->ultimoContrato)->sueldo ?? 0,
                    'seleccionado' => false,
                ];
            })
            ->values()
            ->all();

        $this->mostrarFormularioCambioSueldos = true;

        // importante: dispara el evento para que Alpine cargue la lista
        $this->dispatch('ejecutarCambioSueldos', trabajadores: $lista);
    }
    public function guardarCambiosSueldos($cambios)
    {
        try {
            
            app(ContratoServicio::class)->guardarCambiosSueldos(
                $cambios,
                $this->mesVigencia,
                $this->anioVigencia
            );
            $this->alert('success', 'Sueldos modificados correctamente en su nuevo contrato.');

        } catch (\Throwable $th) {
            return $this->alert('error', $th->getMessage());
        }

    }
    #endregion
    public function render()
    {
        $query = Empleado::query();

        if (!empty($this->search)) {
            $query->where(function ($query) {
                $query->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                    ->orWhere('documento', 'like', '%' . $this->search . '%');
            });
        }

        // Filtro por cargo
        if (!empty($this->cargo_id)) {
            $query->where('cargo_id', $this->cargo_id);
        }

        // Filtro por descuento
        if (!empty($this->descuento_sp_id)) {
            $query->where('descuento_sp_id', $this->descuento_sp_id);
        }

        // Filtro por grupo
        if (!empty($this->grupo_codigo)) {
            if ($this->grupo_codigo === 'sg') {
                $query->whereNull('grupo_codigo');
            } else {
                $query->where('grupo_codigo', $this->grupo_codigo);
            }
        }

        // Filtro por género
        if (!empty($this->genero)) {
            $query->where('genero', $this->genero);
        }

        // Filtro por estado
        if (!empty($this->estado)) {
            $query->where('status', $this->estado);
        }

        // Filtro por tipo de planilla
        if (!empty($this->tipo_planilla)) {
            $query->where('tipo_planilla', $this->tipo_planilla);
        }

        $empleados = $query->orderBy('orden')->with(['ultimoContrato'])->paginate(50);

        return view('livewire.empleados-component', [
            'empleados' => $empleados
        ]);
    }
}
