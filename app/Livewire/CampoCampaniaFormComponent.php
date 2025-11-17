<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CampoCampaniaFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $campaniaId;
    public $fecha_inicio, $nombre_campania, $variedad_tuna, $sistema_cultivo, $tipo_cambio, $pencas_x_hectarea, $fecha_fin;
    public $campoSeleccionado;
    public $errorMensaje = [];
    public $ultimaCampania;
    public $area;
    public $areaOriginal;
    protected $listeners = ['registroCampania', 'editarCampania'];
    public function registroCampania($campoNombre = null)
    {
        $this->resetForm();
        if ($campoNombre) {
            $campo = Campo::find($campoNombre);
            if ($campo) {
                $this->area = $campo->area;
                $this->ultimaCampania = $campo->CampaniaActual;

                $this->campoSeleccionado = $campoNombre;
            }
        }


        $this->mostrarFormulario = true;
    }
    public function updatedCampoSeleccionado($campoNombre)
    {
        $campo = Campo::find($campoNombre);
        if ($campo) {
            $this->area = $campo->area;
            $this->ultimaCampania = $campo->CampaniaActual;
        }
    }
    public function editarCampania($campaniaId)
    {
        $this->resetForm();
        $campania = CampoCampania::find($campaniaId);
        if ($campania) {
            $this->areaOriginal = $campania->campo_model->area;
            $this->campaniaId = $campania->id;
            $this->campoSeleccionado = $campania->campo;
            $this->area = $campania->area;
            $this->fecha_inicio = $campania->fecha_inicio;
            $this->fecha_fin = $campania->fecha_fin;
            $this->nombre_campania = $campania->nombre_campania;
            $this->variedad_tuna = $campania->variedad_tuna;
            $this->sistema_cultivo = $campania->sistema_cultivo;
            $this->tipo_cambio = $campania->tipo_cambio;
            $this->pencas_x_hectarea = $campania->pencas_x_hectarea;
        }

        $this->mostrarFormulario = true;
    }
    public function resetForm()
    {
        $this->resetErrorBag();
        $this->reset(['campaniaId','areaOriginal', 'area', 'fecha_inicio', 'fecha_fin', 'campoSeleccionado', 'errorMensaje', 'nombre_campania', 'ultimaCampania', 'variedad_tuna', 'sistema_cultivo', 'tipo_cambio', 'pencas_x_hectarea']);
        //$this->fecha_inicio = Carbon::now()->format('Y-m-d');
    }
    public function store()
    {
        $this->validate([
            'campoSeleccionado' => 'required',
            'nombre_campania' => 'required|string',
            'area' => 'required|numeric|between:0,99999999.99',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'variedad_tuna' => 'nullable|string|max:50',
            'sistema_cultivo' => 'nullable|string|max:255',
            'tipo_cambio' => 'nullable|numeric|between:0,99999999.99',
        ], [
            'campoSeleccionado.required' => 'El campo es obligatorio.',
            'nombre_campania.required' => 'El nombre de la campaña es obligatorio.',
            'area.required' => 'El área es obligatorio.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio no tiene un formato válido.',
            'fecha_fin.date' => 'La fecha de fin no tiene un formato válido.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'variedad_tuna.max' => 'La variedad de tuna no puede superar los 50 caracteres.',
            'sistema_cultivo.max' => 'El sistema de cultivo no puede superar los 255 caracteres.',
            'tipo_cambio.numeric' => 'El tipo de cambio debe ser un número.',
            'tipo_cambio.between' => 'El tipo de cambio debe estar entre 0 y 99,999,999.99.',
        ]);

        try {
            // Buscar campañas anteriores y posteriores
            $campaniaAnterior = CampoCampania::where('campo', $this->campoSeleccionado)
                ->whereDate('fecha_inicio', '<', $this->fecha_inicio)
                ->orderByDesc('fecha_inicio')
                ->first();

            $campaniaPosterior = CampoCampania::where('campo', $this->campoSeleccionado)
                ->whereDate('fecha_inicio', '>', $this->fecha_inicio)
                ->orderBy('fecha_inicio')
                ->first();

            // Si hay una campaña anterior, actualizar su fecha_fin
            if ($campaniaAnterior) {
                $campaniaAnterior->update([
                    'fecha_fin' => Carbon::parse($this->fecha_inicio)->subDay(),
                ]);
            }

            // Preparar datos
            $data = [
                'nombre_campania' => mb_strtoupper($this->nombre_campania),
                'fecha_inicio' => $this->fecha_inicio,
                'area' => $this->area,
                'usuario_modificador' => Auth::id(),
                'variedad_tuna' => $this->variedad_tuna,
                'sistema_cultivo' => $this->sistema_cultivo,
                'tipo_cambio' => $this->tipo_cambio,
                'pencas_x_hectarea' => $this->pencas_x_hectarea,
            ];

            if ($this->campoSeleccionado) {
                $data['campo'] = $this->campoSeleccionado;
            }

            // Si hay una campaña posterior, definir fecha_fin para la actual
            if ($campaniaPosterior) {
                $data['fecha_fin'] = Carbon::parse($campaniaPosterior->fecha_inicio)->subDay();
            } else {
                $data['fecha_fin'] = $this->fecha_fin; // Usa la fecha ingresada si no hay otra posterior
            }

            // Si existe una campaña con la misma fecha, actualizarla en lugar de crearla
            if ($this->campaniaId) {
                $campoCampania = CampoCampania::findOrFail($this->campaniaId);
                $campoCampania->update($data);
                $mensaje = 'La campaña fue actualizada correctamente.';
            } else {
                // Verificar si ya existe una campaña con la misma fecha en ese campo
                $existeCampania = CampoCampania::where('campo', $this->campoSeleccionado)
                    ->whereDate('fecha_inicio', $this->fecha_inicio)
                    ->first();

                if ($existeCampania) {
                    return $this->alert('error', "Ya existe una campaña para el campo {$this->campoSeleccionado} en la fecha {$existeCampania->fecha_inicio} llamada {$existeCampania->nombre_campania}.");
                }

                CampoCampania::create($data);
                $mensaje = 'La campaña fue registrada correctamente.';
            }

            // Mostrar mensaje de éxito
            $this->alert('success', $mensaje);
            $this->resetForm();
            $this->mostrarFormulario = false;
            $this->dispatch('campaniaInsertada',$data);

        } catch (\Throwable $th) {
            // Captura errores y muestra mensaje
            $this->alert('error', 'Ocurrió un error inesperado #ccfc1.');
            $this->dispatch('log', $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.campo-campania-form-component');
    }
}
