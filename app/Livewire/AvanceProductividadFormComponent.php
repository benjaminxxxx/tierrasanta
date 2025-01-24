<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CuaAsistenciaSemanal;
use App\Models\Labores;
use App\Models\LaborValoracion;
use App\Models\RegistroProductividad;
use App\Models\RegistroProductividadDetalle;
use App\Models\RegistrosProductividadFactor;
use App\Services\ProductividadServicio;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AvanceProductividadFormComponent extends Component
{
    use LivewireAlert;
    public $factor;
    public $campos;
    public $labores;
    public $campoSeleccionado;
    public $laborSeleccionada;
    public $fecha;
    public $mostrarFormulario = false;
    public $actividades = [];
    public $registroId;
    public $valoracion;
    protected $listeners = ['nuevoRegistro', 'editarRegistro', 'factorRegistrado'];
    public function mount()
    {
        $this->inicializarValores();
    }
    public function inicializarValores(){
        $this->labores = Labores::orderBy('nombre_labor')->where('bono', true)->get();
        $this->campos = Campo::orderBy('nombre')->get();

        if ($this->labores->count() > 0) {
            $this->laborSeleccionada = $this->labores->first()->id;    
        }
        if ($this->campos->count() > 0) {
            $this->campoSeleccionado = $this->campos->first()->nombre;
        }
    }
    public function revisarValoraciones()
    {
        if(!$this->fecha){
           return; 
        }
        $labor = Labores::find($this->laborSeleccionada);
        if(!$labor){
            return $this->alert('error','La labor seleccionada ya no existe.');
        }
        $this->valoracion = $labor->valoraciones()->orderBy('vigencia_desde','desc')->whereDate('vigencia_desde', "<=", $this->fecha)
        ->first();
        
        if(!$this->valoracion){
            return;
        }
        $this->actividades = [];
        $this->agregarActividad();
        $this->agregarActividad();

        // Recalcular para todas las actividades cuando cambia la labor seleccionada
        foreach ($this->actividades as $indice => $actividad) {
            $horas = $actividad['horas'] ?? 0;
            $this->recalcularKg($indice, (float)$horas);
        }
    }

    public function updatedActividades($valor, $clave)
    {
        $indice = explode('.', $clave)[0];
        $campo = explode('.', $clave)[1];

        if ($campo === 'horas') {
            $this->recalcularKg($indice, (float)$valor);
        }
    }
    public function updatedFecha()
    {
        $this->revisarValoraciones();
    }

    public function updatedLaborSeleccionada()
    {
        $this->revisarValoraciones();
        
    }

    private function recalcularKg($indice, $horas)
    {
        if (!$this->valoracion || !$this->laborSeleccionada) {
            return; // Salir si no hay datos válidos
        }

        if (isset($this->actividades[$indice])) {
            $kgPorHora = $this->valoracion->kg_8 / 8;
            $this->actividades[$indice]['kg'] = max(0, $horas) * $kgPorHora;
        }
    }
    public function nuevoRegistro($fecha)
    {        
        $this->inicializarValores();
        $this->fecha = null;
        $this->mostrarFormulario = true;
        $this->fecha = $fecha;
        $this->registroId = null;
        $this->revisarValoraciones();
    }
    public function editarRegistro($registroId)
    {
        $this->inicializarValores();
        $registro = RegistroProductividad::find($registroId);
        if (!$registro) {
            return $this->alert('error', 'Registro no encontrado.');
        }
       
       
        $this->registroId = $registroId;
        $this->laborSeleccionada = $registro->labor_id;
        $this->fecha = $registro->fecha;
        $this->campoSeleccionado = $registro->campo;
        $this->revisarValoraciones();
        $this->actividades = [];

        if ($registro->detalles && $registro->detalles->count() > 0) {
            foreach ($registro->detalles as $detalle) {
                $this->actividades[] = [
                    'horas' => $detalle->horas_trabajadas,
                    'kg' => $detalle->kg,
                ];
            }
        }
        
        $this->mostrarFormulario = true;
    }
    public function agregarActividad()
    {
        $this->actividades[] = [
            'horas' => 0,
            'kg' => 0
        ];
    }
    public function registrarAvance()
    {
        $this->validate(
            [
                'fecha' => 'required|date',
                'campoSeleccionado' => 'required|string',
                'laborSeleccionada' => 'required|integer|exists:labores,id',
            ],
            [
                'fecha.required' => 'El campo "Fecha" es obligatorio.',
                'fecha.date' => 'El campo "Fecha" debe ser una fecha válida.',

                'campoSeleccionado.required' => 'El campo "Campo" es obligatorio.',
                'campoSeleccionado.string' => 'El campo "Campo" debe ser un texto válido.',

                'laborSeleccionada.required' => 'El campo "Labor" es obligatorio.',
                'laborSeleccionada.integer' => 'El campo "Labor" debe ser un número válido.',
                'laborSeleccionada.exists' => 'La labor seleccionada no existe en la base de datos.',
            ]
        );

        

        try {

            $productividadId = null;

            if ($this->registroId) {
                $productividadId = $this->registroId;
                $registro = RegistroProductividad::find($this->registroId);

                if ($registro) {
                    $registro->update([
                        'labor_valoracion_id'=>$this->valoracion->id,
                        'labor_id' => $this->laborSeleccionada,
                        'fecha' => $this->fecha,
                        'campo' => $this->campoSeleccionado,
                    ]);

                    // Registrar los nuevos detalles
                    if (count($this->actividades) > 0) {
                        foreach ($this->actividades as $indice => $actividad) {
                            $horas = $actividad['horas'] ?? 0;
                            $kg = $actividad['kg'] ?? 0;
                            $detalleRegistrado = RegistroProductividadDetalle::where('indice',$indice + 1)
                            ->where('registro_productividad_id',$registro->id)
                            ->first();
                            if($detalleRegistrado){
                                $detalleRegistrado->update([
                                    'horas_trabajadas' => $horas,
                                    'kg' => $kg,
                                ]);
                            }else{
                                RegistroProductividadDetalle::create([
                                    'indice' => $indice + 1,
                                    'registro_productividad_id' => $registro->id,
                                    'horas_trabajadas' => $horas,
                                    'kg' => $kg,
                                ]);
                            }
                        }
                    }
                }
            } else {
                //se procede a registrar del array de datos
                $registro = RegistroProductividad::create([
                    'labor_valoracion_id'=>$this->valoracion->id,
                    'labor_id' => $this->laborSeleccionada,
                    'fecha' => $this->fecha,
                    'campo' => $this->campoSeleccionado,
                ]);
                $productividadId = $registro->id;

                if ($registro && count($this->actividades) > 0) {
                    foreach ($this->actividades as $indice => $actividad) {
                        $horas = $actividad['horas'] ?? 0;
                        $kg = $actividad['kg'] ?? 0;
                        RegistroProductividadDetalle::create([
                            'indice' => $indice + 1,
                            'registro_productividad_id' => $registro->id,
                            'horas_trabajadas' => $horas,
                            'kg' => $kg,
                        ]);
                    }
                }
            }

            $this->actividades = [];

            $productividadServicio = new ProductividadServicio($productividadId);
            $productividadServicio->registrarBonos();

            $this->alert('success', 'Registro exitoso.');
            $this->mostrarFormulario = false;
            $this->dispatch('nuevoRegistroAvance');
        } catch (\Throwable $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error al registrar el avance.');
        }
    }
    public function quitarActividad($indice)
    {
        unset($this->actividades[$indice]);
    }
    public function render()
    {
        return view('livewire.avance-productividad-form-component');
    }
}
