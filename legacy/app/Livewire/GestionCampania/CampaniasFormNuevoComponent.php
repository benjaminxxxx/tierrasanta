<?php

namespace App\Livewire\GestionCampania;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\CrudCampaniaServicio;
use App\Support\SugerenciaHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Validation\Rule;

class CampaniasFormNuevoComponent extends Component
{
    use LivewireAlert;

    public $mostrarFormulario = false;
    public $tabActual;
    public $campania = [];
    public $campaniaAnterior;
    protected $listeners = ['registroCampania'];
    public function mount()
    {
        $this->campania = [
            'campo' => null
        ];
    }
    public function updatedCampaniaCampo($nuevoCampo)
    {
        // Ejemplo: obtener el área desde la tabla campos
        $campo = Campo::where('nombre', $nuevoCampo)->first();
        $this->campania['nombre_campania'] = null;
        $this->campania['area'] = null;
        $this->campania['variedad_tuna'] = null;
        $this->campaniaAnterior = null;
        if ($campo) {
            $this->campaniaAnterior = CampoCampania::whereDate('fecha_inicio', '<', now())
                ->orderBy('fecha_inicio', 'desc')
                ->first();
            if ($this->campaniaAnterior) {
                $this->campania['nombre_campania'] = SugerenciaHelper::sugerirSiguienteNombreCampania($this->campaniaAnterior->nombre_campania);
                $this->campania['variedad_tuna'] = $this->campaniaAnterior['variedad_tuna'];
            }


            $this->campania['area'] = $campo->area; // o el cálculo que necesites
        }
    }
    public function registroCampania()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['campania']);
    }
    public function guardarCampania()
    {
        $this->validate([
            'campania.campo' => 'required',
            'campania.nombre_campania' => [
                'required',
                'string',
                Rule::unique('campos_campanias', 'nombre_campania')
                    ->where(fn($q) => $q->where('campo', $this->campania['campo'])),
            ],
            'campania.area' => 'required|numeric|between:0,99999999.99',
            'campania.fecha_inicio' => 'required|date',
        ]);

        try {
            $data = $this->campania;
            $data['nombre_campania'] = mb_strtoupper($data['nombre_campania']);

            $campania = app(CrudCampaniaServicio::class)->guardar($data);

            $this->alert('success', 'La campaña fue registrada correctamente.');

            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->dispatch('campaniaInsertada',$campania->toArray());

        } catch (\Throwable $e) {
            $this->alert('error', $e->getMessage());
            $this->dispatch('log', $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.gestion-campania.campanias-form-nuevo-component');
    }
}
