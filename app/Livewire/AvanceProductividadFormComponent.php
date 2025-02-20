<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\Labores;
use App\Models\RegistroProductividad;
use App\Models\RegistroProductividadDetalle;
use App\Services\ProductividadServicio;
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
    public $kg8;
    public $valorKgAdicional;
    protected $listeners = ['nuevoRegistro', 'editarRegistro', 'factorRegistrado'];
    public function mount()
    {
        $this->inicializarValores();
    }
    public function inicializarValores()
    {
        $this->labores = Labores::orderBy('nombre_labor')->where('bono', true)->get();
        $this->campos = Campo::orderBy('nombre')->get();

        if ($this->labores->count() > 0) {
            $this->laborSeleccionada = $this->labores->first()->id;
        }
        if ($this->campos->count() > 0) {
            $this->campoSeleccionado = $this->campos->first()->nombre;
        }

        $this->kg8 = null;
        $this->valorKgAdicional = null;
    }
    public function revisarValoraciones()
    {
        if (!$this->fecha) {
            return;
        }
        $labor = Labores::find($this->laborSeleccionada);
        if (!$labor) {
            return $this->alert('error', 'La labor seleccionada ya no existe.');
        }

        
        $this->valoracion = $labor->valoraciones()->orderBy('vigencia_desde', 'desc')->whereDate('vigencia_desde', "<=", $this->fecha)
            ->first();

        if (!$this->valoracion) {
            $this->kg8 = null;
            $this->valorKgAdicional = null;
            return;
        }

        $this->kg8 = $this->valoracion->kg_8;
        $this->valorKgAdicional = $this->valoracion->valor_kg_adicional;
        $this->actividades = [];
        $this->agregarActividad();
        $this->agregarActividad();

    }

    
    public function updatedFecha()
    {
        $this->revisarValoraciones();
    }

    public function updatedLaborSeleccionada()
    {
        $this->revisarValoraciones();
    }
 
   
    public function nuevoRegistro($fecha)
    {
        $this->resetErrorBag();
        
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
        $this->kg8 = $registro->kg_8;
        $this->valorKgAdicional = $registro->valor_kg_adicional;
        $this->actividades = [];

        $labor = Labores::find($this->laborSeleccionada);
        if (!$labor) {
            return $this->alert('error', 'La labor seleccionada ya no existe.');
        }

        
        $this->valoracion = $labor->valoraciones()->orderBy('vigencia_desde', 'desc')->whereDate('vigencia_desde', "<=", $this->fecha)
            ->first();

        if ($registro->detalles && $registro->detalles->count() > 0) {
            foreach ($registro->detalles as $detalle) {
                $this->actividades[] = [
                    'horas' => $detalle->horas_trabajadas,
                    'kg' => $detalle->kg,
                    'id' => $detalle->id,
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
                'kg8' => 'required',
                'valorKgAdicional' => 'required',
            ],
            [
                'fecha.required' => 'El campo "Fecha" es obligatorio.',
                'fecha.date' => 'El campo "Fecha" debe ser una fecha válida.',

                'kg8.required' => 'El campo es obligatorio.',
                'valorKgAdicional.required' => 'El campo es obligatorio.',

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
                        'labor_valoracion_id' => $this->valoracion->id,
                        'labor_id' => $this->laborSeleccionada,
                        'fecha' => $this->fecha,
                        'campo' => $this->campoSeleccionado,
                        'kg_8'=> $this->kg8,
                        'valor_kg_adicional'=> $this->valorKgAdicional,
                    ]);

                    // Registrar los nuevos detalles
                    if (count($this->actividades) > 0) {
                        $idDetallesExistentes = [];
                        foreach ($this->actividades as $indice => $actividad) {
                            $horas = $actividad['horas'] ?? 0;

                            $kgPorHora = $this->kg8 / 8;
                            $kg = max(0, $horas) * $kgPorHora;
                            
                            $idDetalle = $actividad['id'] ?? null;
                            if($idDetalle){
                                $detalleRegistrado = RegistroProductividadDetalle::find($idDetalle);

                                if ($detalleRegistrado) {
                                    $idDetallesExistentes[] = $detalleRegistrado->id;
                                    $detalleRegistrado->update([
                                        'indice' => $indice + 1,
                                        'horas_trabajadas' => $horas,
                                        'kg' => $kg,
                                    ]);
                                }
                            }
                            
                             else {
                                $detalleRegistrado = RegistroProductividadDetalle::create([
                                    'indice' => $indice + 1,
                                    'registro_productividad_id' => $registro->id,
                                    'horas_trabajadas' => $horas,
                                    'kg' => $kg,
                                ]);
                                $idDetallesExistentes[] = $detalleRegistrado->id;
                            }
                        }
                        $seHanEliminado = false;
                        $detallesActuales = RegistroProductividad::find($this->registroId)->detalles->pluck('id')->toArray();
                        foreach ($detallesActuales as $detallesActuales) {
                            if(!in_array($detallesActuales,$idDetallesExistentes)){
                               $seHanEliminado = true;
                                RegistroProductividadDetalle::find($detallesActuales)->delete();
                            }
                        }
                        
                        if($seHanEliminado){
                            //lo hago con nueva instancia para que me traiga los valores actualizados de detalle
                            $detalleActual2 = RegistroProductividad::find($this->registroId)->detalles;
                           
                            foreach ($detalleActual2 as $indice1 => $detalleActual) {
                                $detalleActual->update([
                                    'indice'=>$indice1+1
                                ]);
                            }
                        }
                    }
                }
            } else {
                //se procede a registrar del array de datos
                $registro = RegistroProductividad::create([
                    'labor_valoracion_id' => $this->valoracion->id,
                    'labor_id' => $this->laborSeleccionada,
                    'fecha' => $this->fecha,
                    'campo' => $this->campoSeleccionado,
                    'kg_8'=> $this->kg8,
                    'valor_kg_adicional'=> $this->valorKgAdicional,
                ]);
                $productividadId = $registro->id;

                if ($registro && count($this->actividades) > 0) {
                    foreach ($this->actividades as $indice => $actividad) {
                        $horas = $actividad['horas'] ?? 0;
                        $kgPorHora = $this->kg8 / 8;
                        $kg = max(0, $horas) * $kgPorHora;
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
