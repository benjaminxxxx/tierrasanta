<?php

namespace App\Livewire\Cuadrilla\Cuadrillero;

use App\Models\CuaGrupo;
use App\Services\Cuadrilla\Grupo\CuadrillaGrupoConsulta;
use App\Services\Cuadrilla\Grupo\CuadrillaGrupoReglas;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class CuadrillaGruposComponent extends Component
{
    use LivewireAlert, WithPagination, WithoutUrlPagination, \App\Traits\HandlesAlerts;
    public $verEliminados;
    public string $buscar = '';
    public bool $soloInactivos = false;
    public string $orden = 'created_at';
    public string $direccion = 'desc';

    public bool $mostrarEstadistica = false;
    public ?int $anioEstadistica = null;

    protected $listeners = ['confirmarEliminar', 'grupoRegistrado' => '$refresh'];
    public function restaurar($codigo)
    {
        CuaGrupo::where('codigo', $codigo)->restore();
        $this->resetPage();
        $this->alert('success', 'Registro restaurado correctamente.');
    }
    public function updatedVerEliminados()
    {
        $this->resetPage();
    }
    public function updatedBuscar()
    {
        $this->resetPage();
    }
    public function updatedSoloInactivos()
    {
        $this->resetPage();
    }
    /** Clic en un encabezado: ordena por esa columna; otro clic invierte el sentido. */
    public function sortBy(string $columna)
    {
        if (!in_array($columna, CuadrillaGrupoConsulta::ORDENES, true)) {
            return;
        }
        if ($this->orden === $columna) {
            $this->direccion = $this->direccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orden = $columna;
            $this->direccion = in_array($columna, ['nombre', 'codigo'], true) ? 'asc' : 'desc';
        }
        $this->resetPage();
    }
    public function abrirEstadistica()
    {
        $this->anioEstadistica = null;
        $this->mostrarEstadistica = true;
    }
    public function eliminarGrupoCuadrilla($codigo)
    {
        try {
            CuaGrupo::where('codigo', $codigo)->delete();
            $this->resetPage();
            $this->alert('success', 'Registro Eliminado Correctamente.');
        } catch (\Throwable $th) {
            $this->errorAlert($th);
        }
    }
    public function render(CuadrillaGrupoConsulta $consulta)
    {
        $grupos = $consulta->listado($this->buscar, $this->orden, $this->direccion, (bool) $this->verEliminados, $this->soloInactivos)
            ->paginate(10);

        // La estadística solo se calcula con el modal abierto
        $estadistica = [];
        if ($this->mostrarEstadistica) {
            $estadistica = $consulta->participacionPorAnio();
            if (!$this->anioEstadistica || !isset($estadistica[$this->anioEstadistica])) {
                $this->anioEstadistica = array_key_first($estadistica);
            }
        }

        return view('livewire.cuadrilla.cuadrillero.cuadrilla-grupos-component', [
            'grupos' => $grupos,
            'mesesSinTramo' => CuadrillaGrupoReglas::mesesSinTramo(),
            'fechaLimite' => CuadrillaGrupoConsulta::fechaLimiteInactivo(),
            'estadistica' => $estadistica,
        ]);
    }
}
