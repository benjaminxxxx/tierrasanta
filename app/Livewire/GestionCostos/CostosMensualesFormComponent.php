<?php

namespace App\Livewire\GestionCostos;
use App\Models\CostoMensual;
use App\Services\Contabilidad\CostosMensualesServicio;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CostosMensualesFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormCostosMensuales = false;
    public $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    public $paso = 1;
    public $form = [
        'anio' => null,
        'mes' => null
    ];
    protected $listeners = ['agregarCostoMensual'];
    public function agregarCostoMensual()
    {
        $this->reset(['form']);
        $this->form = [
            'anio' => null,
            'mes' => null
        ];
        $this->mostrarFormCostosMensuales = true;
    }
    public function cargarCostoMensual(): void
    {
        $this->validate([
            'form.anio' => 'required|integer',
            'form.mes' => 'required|integer',
        ]);

        $costo = CostoMensual::where('anio', $this->form['anio'])
            ->where('mes', $this->form['mes'])
            ->first();

        if ($costo) {
            $this->form = array_merge($this->form, $costo->toArray());
        }
        $this->paso = 2;
    }
    public function guardarCostoMensual()
    {
        try {

            app(CostosMensualesServicio::class)->guardar($this->form);
            $this->mostrarFormCostosMensuales = false;
            $this->dispatch('actualizarCostosMensualesTable');
            $this->alert('success', 'Costos guardados correctamente.');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function render()
    {
        $filteredData = CostoMensual::paginate(20);
        return view('livewire.gestion-costos.costos-mensuales-form-component', [
            'filteredData' => $filteredData
        ]);
    }
}