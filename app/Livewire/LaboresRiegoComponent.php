<?php

namespace App\Livewire;

use App\Models\LaboresRiego;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class LaboresRiegoComponent extends Component
{
    use LivewireAlert, WithPagination;

    // Búsqueda y filtro (ya no es un formulario de alta)
    public $busqueda = '';
    public $filtroTipo = ''; // '', 'riego', 'apoyo', 'ninguno'

    // Estado del modal (crear Y editar comparten el mismo modal)
    public $mostrarModal = false;
    public $laborEditandoId = null;
    public $nombreLabor;
    public $tipoLabor = ''; // '', 'riego', 'apoyo' — un solo valor, mutuamente excluyente por diseño
    public $consumoM3Hora;

    protected function rules()
    {
        return [
            'nombreLabor' => 'required|string|max:255',
            'tipoLabor' => 'nullable|in:riego,apoyo',
            'consumoM3Hora' => 'nullable|numeric|min:0',
        ];
    }

    public function updatingBusqueda()
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo()
    {
        $this->resetPage();
    }


    // --- Normalización para detectar duplicados sin importar mayúsculas/símbolos ---
    private function normalizarNombre(string $nombre): string
    {
        $sinAcentos = Str::ascii($nombre);
        $soloAlfanumerico = preg_replace('/[^a-zA-Z0-9]/', '', $sinAcentos);
        return mb_strtolower($soloAlfanumerico);
    }

    private function existeNombreDuplicado(string $nombre, ?int $ignorarId = null): bool
    {
        $normalizadoNuevo = $this->normalizarNombre($nombre);

        return LaboresRiego::all()
            ->when($ignorarId, fn($col) => $col->reject(fn($l) => $l->id === $ignorarId))
            ->contains(fn($l) => $this->normalizarNombre($l->nombre_labor) === $normalizadoNuevo);
    }

    // --- Modal: abrir en modo CREAR ---
    public function abrirCrear()
    {
        $this->resetValidation();
        $this->laborEditandoId = null;
        $this->nombreLabor = null;
        $this->tipoLabor = '';
        $this->consumoM3Hora = null;
        $this->mostrarModal = true;
    }

    // --- Modal: abrir en modo EDITAR ---
    public function abrirEditar($id)
    {
        $this->resetValidation();
        $labor = LaboresRiego::findOrFail($id);

        $this->laborEditandoId = $labor->id;
        $this->nombreLabor = $labor->nombre_labor;
        $this->tipoLabor = $labor->es_riego ? 'riego' : ($labor->es_apoyo_riego ? 'apoyo' : '');
        $this->consumoM3Hora = $labor->consumo_m3_hora;

        $this->mostrarModal = true;
    }

    // --- Guardar: crea o actualiza según laborEditandoId ---
    public function guardar()
    {
        $this->validate();

        if ($this->existeNombreDuplicado($this->nombreLabor, $this->laborEditandoId)) {
            $this->addError('nombreLabor', 'Ya existe una labor con un nombre igual o muy similar (se ignoran mayúsculas y símbolos).');
            return;
        }

        $datos = [
            'nombre_labor' => trim($this->nombreLabor), // se guarda tal como el usuario lo escribió
            'es_riego' => $this->tipoLabor === 'riego',
            'es_apoyo_riego' => $this->tipoLabor === 'apoyo',
            'consumo_m3_hora' => $this->tipoLabor === 'riego' ? $this->consumoM3Hora : null,
        ];

        if ($this->laborEditandoId) {
            LaboresRiego::findOrFail($this->laborEditandoId)->update($datos);
            $this->alert('success', 'Labor actualizada correctamente');
        } else {
            LaboresRiego::create($datos);
            $this->alert('success', 'Labor agregada correctamente');
        }

        $this->mostrarModal = false;
    }

    public function eliminarLabor($id)
    {
        try {
            LaboresRiego::findOrFail($id)->delete();
            $this->alert('success', 'Labor eliminada correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', 'No se pudo eliminar la labor: ' . $th->getMessage());
        }
    }
    public function render()
    {
        $query = LaboresRiego::query();

        if ($this->busqueda) {
            $query->where('nombre_labor', 'like', '%' . $this->busqueda . '%');
        }

        if ($this->filtroTipo === 'riego') {
            $query->where('es_riego', true);
        } elseif ($this->filtroTipo === 'apoyo') {
            $query->where('es_apoyo_riego', true);
        } elseif ($this->filtroTipo === 'ninguno') {
            $query->where('es_riego', false)->where('es_apoyo_riego', false);
        }

        $labores = $query->orderBy('nombre_labor')->paginate(10);

        return view('livewire.labores-riego-component', compact('labores'));
    }

}