<?php

namespace App\Livewire;

use App\Models\Kardex;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class KardexFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $fecha_inicial;
    public $fecha_final;
    public $anioSeleccionado;
    public $anios = [];
    public $nombre;
    public $tipoKardex;
    protected $listeners = ['crearKardex'];
    protected $rules = [
        "anioSeleccionado" => "required|string",
        "tipoKardex" => "required|string",
        "fecha_inicial" => "required|date",
        "fecha_final" => "required|date",
    ];
    protected $messages = [
        "anioSeleccionado.required" => "El año es requerido",
        "tipoKardex.required" => "El tipo de kardex es requerido",
        "fecha_inicial.required" => "La fecha inicial es requerido",
        "fecha_inicial.date" => "La fecha inicial no tiene un formato válido",
        "fecha_final.required" => "La fecha final es requerido",
        "fecha_final.date" => "La fecha final no tiene un formato válido",
    ];
    public function mount()
    {
        $anioActual = Carbon::now()->year;
        $this->anios = [];

        for ($i = 2015; $i <= $anioActual + 1; $i++) {
            $this->anios[] = $i;
        }
        $this->tipoKardex = 'normal';
    }
    public function updatedAnioSeleccionado($anio)
    {
        if ($anio) {
            $this->fecha_inicial = Carbon::create($anio, 1, 1)->toDateString();     // yyyy-01-01
            $this->fecha_final = Carbon::create($anio, 12, 31)->toDateString();     // yyyy-12-31
        } else {
            $this->fecha_inicial = null;
            $this->fecha_final = null;
        }
    }

    public function crearKardex()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function storeKardexForm()
    {

        $data = $this->validate();

        $fechaInicial = Carbon::parse($data["fecha_inicial"]);
        if ($this->fecha_final) {
            $fechaFinal = Carbon::parse($data["fecha_final"]);
            if ($fechaFinal->lte($fechaInicial)) {
                return $this->alert("error", "La fecha final debe ser mayor que la fecha inicial");
            }
        }

        try {
            
            $es_combsutible = $this->tipoKardex == 'combustible';
            $nombre = 'Kardex ' . ($es_combsutible?'combustible':'') . ' '. $this->anioSeleccionado;
            $existe = Kardex::where('anio',$this->anioSeleccionado)
            ->where('es_combustible',$es_combsutible)
            ->exists();

            if($existe){
                return $this->alert('warning',"El Kardex para el año {$this->anioSeleccionado} ya existe");
            }
            Kardex::create([
                'nombre'=>$nombre,
                'anio'=>$this->anioSeleccionado,
                'es_combustible'=>$es_combsutible,
                'fecha_inicial'=>$data["fecha_inicial"],
                'fecha_final'=>$data["fecha_final"],
            ]);
            $this->dispatch("kardexRegistrado");
            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->alert("success", "Registro de Kardex exitoso");
        } catch (\Throwable $th) {
            $this->alert("error", $th->getMessage());
        }
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['anioSeleccionado', 'fecha_inicial', 'fecha_final']);
    }
    public function render()
    {
        return view('livewire.kardex-form-component');
    }
}
