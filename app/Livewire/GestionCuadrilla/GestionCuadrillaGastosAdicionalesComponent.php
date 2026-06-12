<?php
// app/Livewire/GestionCuadrilla/GestionCuadrillaGastosAdicionalesComponent.php
namespace App\Livewire\GestionCuadrilla;

use App\Models\CuadTramoLaboral;
use App\Models\GastoAdicionalPorGrupoCuadrilla;
use App\Services\Cuadrilla\GastoAdicionalServicio;
use App\Services\Cuadrilla\TramoLaboralServicio;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class GestionCuadrillaGastosAdicionalesComponent extends Component
{
    use LivewireAlert;

    // ── Estado del modal ──────────────────────────────────────────
    public bool $mostrarFormulario = false;

    // ── Tramo y datos de contexto ─────────────────────────────────
    public $tramoLaboral;
    public array $grupos = [];
    public string $fechaDefault = '';

    // ── Lista de gastos existentes (solo lectura, para la tabla) ──
    public array $gastosExistentes = [];

    // ── Formulario de nuevo gasto ─────────────────────────────────
    public string $nuevoGrupo       = '';
    public string $nuevoDescripcion = '';
    public string $nuevoFecha       = '';
    public string $nuevoMonto       = '';

    // ── Edición inline ─────────────────────────────────────────────
    public ?int $editandoId = null;
    public array $editDatos = [];

    protected $listeners = ['abrirGastosAdicionales'];

    public function mount(int $tramoId): void
    {
        $this->tramoLaboral = app(TramoLaboralServicio::class)->encontrarTramoPorId($tramoId);
        $this->grupos = $this->tramoLaboral->grupos()->get()->pluck('nombre')->toArray();
    }

    // ── Abrir modal ───────────────────────────────────────────────
    public function abrirGastosAdicionales(): void
    {
        $this->grupos = $this->tramoLaboral->grupos()->get()->pluck('nombre')->toArray();
        $this->cargarGastosExistentes();
        $this->inicializarFormularioNuevo();
        $this->editandoId = null;
        $this->editDatos  = [];
        $this->mostrarFormulario = true;
    }

    private function cargarGastosExistentes(): void
    {
        $servicio = app(GastoAdicionalServicio::class);
        $this->gastosExistentes = $servicio->listarPorTramo($this->tramoLaboral)->toArray();
    }

    private function inicializarFormularioNuevo(): void
    {
        $inicio = Carbon::parse($this->tramoLaboral->fecha_inicio);
        $fin    = Carbon::parse($this->tramoLaboral->fecha_fin);
        $hoy    = Carbon::today();

        $this->fechaDefault = $hoy->between($inicio, $fin)
            ? $hoy->toDateString()
            : $inicio->toDateString();

        $this->nuevoGrupo       = '';
        $this->nuevoDescripcion = '';
        $this->nuevoFecha       = $this->fechaDefault;
        $this->nuevoMonto       = '';
    }

    // ── CRUD individual ───────────────────────────────────────────

    public function agregarGasto(): void
    {
        $this->validate([
            'nuevoGrupo'       => 'required|string',
            'nuevoDescripcion' => 'required|string|max:255',
            'nuevoFecha'       => 'required|date',
            'nuevoMonto'       => 'required|numeric|min:0.01',
        ], [
            'nuevoGrupo.required'       => 'Seleccione un grupo.',
            'nuevoDescripcion.required' => 'Ingrese una descripción.',
            'nuevoFecha.required'       => 'Ingrese la fecha.',
            'nuevoMonto.required'       => 'Ingrese el monto.',
            'nuevoMonto.min'            => 'El monto debe ser mayor a 0.',
        ]);

        try {
            app(GastoAdicionalServicio::class)->crear($this->tramoLaboral, [
                'grupo'       => $this->nuevoGrupo,
                'descripcion' => $this->nuevoDescripcion,
                'fecha'       => $this->nuevoFecha,
                'monto'       => $this->nuevoMonto,
            ]);

            $this->cargarGastosExistentes();
            $this->inicializarFormularioNuevo();
            $this->alert('success', 'Gasto agregado correctamente.');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function iniciarEdicion(int $id): void
    {
        $gasto = GastoAdicionalPorGrupoCuadrilla::findOrFail($id);

        if (!$gasto->estaEditablePor(Auth::user())) {
            $this->alert('error', 'No tiene permisos para editar este gasto.');
            return;
        }

        $this->editandoId = $id;
        $this->editDatos  = [
            'grupo'       => $gasto->grupo?->nombre ?? '',
            'descripcion' => $gasto->descripcion,
            'fecha'       => Carbon::parse($gasto->fecha_gasto)->toDateString(),
            'monto'       => $gasto->monto,
        ];
    }

    public function guardarEdicion(): void
    {
        $this->validate([
            'editDatos.grupo'       => 'required|string',
            'editDatos.descripcion' => 'required|string|max:255',
            'editDatos.fecha'       => 'required|date',
            'editDatos.monto'       => 'required|numeric|min:0.01',
        ]);

        try {
            $gasto = GastoAdicionalPorGrupoCuadrilla::findOrFail($this->editandoId);
            app(GastoAdicionalServicio::class)->editar($gasto, $this->tramoLaboral, $this->editDatos);

            $this->editandoId = null;
            $this->editDatos  = [];
            $this->cargarGastosExistentes();
            $this->alert('success', 'Gasto actualizado.');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function cancelarEdicion(): void
    {
        $this->editandoId = null;
        $this->editDatos  = [];
    }

    public function eliminarGasto(int $id): void
    {
        try {
            $gasto = GastoAdicionalPorGrupoCuadrilla::findOrFail($id);
            app(GastoAdicionalServicio::class)->eliminar($gasto);

            $this->cargarGastosExistentes();
            $this->alert('success', 'Gasto eliminado.');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    // ── Aprobación ─────────────────────────────────────────────────
    public function aprobarTodos(): void
    {
        try {
            $n = app(GastoAdicionalServicio::class)->aprobarTodos($this->tramoLaboral);
            $this->cargarGastosExistentes();
            $this->alert('success', "{$n} gasto(s) aprobados y sellados.");
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    public function habilitarCorreccion(int $id): void
    {
        try {
            $gasto = GastoAdicionalPorGrupoCuadrilla::findOrFail($id);
            app(GastoAdicionalServicio::class)->habilitarParaCorreccion($gasto);

            $this->cargarGastosExistentes();
            $this->alert('success', 'Gasto habilitado para corrección.');
        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
        }
    }

    // ── Cierre ─────────────────────────────────────────────────────
    public function cerrar(): void
    {
        $this->mostrarFormulario = false;
    }

    public function render()
    {
        return view('livewire.gestion-cuadrilla.gestion-cuadrilla-gastos-adicionales-component');
    }
}