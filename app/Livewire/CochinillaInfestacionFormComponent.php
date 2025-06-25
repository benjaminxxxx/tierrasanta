<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Services\Cochinilla\InfestacionServicio;
use App\Services\Cochinilla\IngresoServicio;
use App\Services\CochinillaIngresoServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Log;

class CochinillaInfestacionFormComponent extends Component
{
    use LivewireAlert;
    public $mostrarFormulario = false;
    public $cochinillaInfestacionId;
    public $tipo_infestacion = 'infestacion';
    public $campoSeleccionado;
    public $area;
    public $fecha;
    public $campania;
    public $kg_madres;
    public $kg_madres_por_ha;
    public $campo_origen_nombre;
    public $metodo;
    public $numero_envases;
    public $capacidad_envase;
    public $infestadores;
    public $madres_por_infestador;
    public $infestadores_por_ha;
    public $campoSeleccionadoOrigen;
    public $kg_madres_ha;
    public $cochinillaIngresoRelacionados = [];
    public $kgAsignados = [];
    public $ingresosSeleccionados = [];
    protected $listeners = ['agregarInfestacion', 'editarInfestacion'];
    public function mount()
    {
        $this->resetForm();
    }
    public function resetForm()
    {
        $this->reset([
            'cochinillaInfestacionId',
            'tipo_infestacion',
            'fecha',
            'campoSeleccionado',
            'area',
            'kg_madres',
            'kg_madres_por_ha',
            'campoSeleccionadoOrigen',
            'metodo',
            'numero_envases',
            'capacidad_envase',
            'infestadores',
            'madres_por_infestador',
            'infestadores_por_ha',
            'campania'
        ]);
        $this->cochinillaIngresoRelacionados = [];
        // Ahora reestableces los valores por defecto
        $this->fecha = Carbon::now()->format('Y-m-d');
        $this->tipo_infestacion = 'infestacion';
        $this->mostrarFormulario = false;
    }
    public function agregarInfestacion()
    {
        $this->resetForm();
        $this->mostrarFormulario = true;
    }
    public function editarInfestacion($cochinillaInfestacionId)
    {

        $cochinillaInfestacion = CochinillaInfestacion::find($cochinillaInfestacionId);
        $this->resetForm();

        if ($cochinillaInfestacion) {
            $this->cochinillaInfestacionId = $cochinillaInfestacion->id;
            $this->tipo_infestacion = $cochinillaInfestacion->tipo_infestacion;
            $this->fecha = $cochinillaInfestacion->fecha;
            $this->campoSeleccionado = $cochinillaInfestacion->campo_nombre;
            $this->area = $cochinillaInfestacion->area;
            $this->kg_madres = $cochinillaInfestacion->kg_madres;
            $this->kg_madres_por_ha = number_format($cochinillaInfestacion->kg_madres_por_ha, 2);
            $this->campoSeleccionadoOrigen = $cochinillaInfestacion->campo_origen_nombre;
            $this->metodo = $cochinillaInfestacion->metodo;
            $this->numero_envases = $cochinillaInfestacion->numero_envases;
            $this->capacidad_envase = $cochinillaInfestacion->capacidad_envase;
            $this->infestadores = $cochinillaInfestacion->infestadores;
            $this->madres_por_infestador = number_format($cochinillaInfestacion->madres_por_infestador, 6);
            $this->infestadores_por_ha = $cochinillaInfestacion->infestadores_por_ha;
            $this->mostrarFormulario = true;
            $this->buscarCampania();
            $this->obtenerIngresosSeleccionados($this->campoSeleccionadoOrigen);
        }
    }

    public function registrar()
    {
        $this->validate([
            'tipo_infestacion' => 'required|string|max:255',
            'fecha' => 'required|date',
            'campoSeleccionado' => 'required|string|max:255',
            'area' => 'required|numeric|min:0.01',
            'kg_madres' => 'required|numeric|min:0',
            'metodo' => 'required|string|max:255',
            'numero_envases' => 'required|integer|min:0',
            'capacidad_envase' => 'required|numeric|min:0',
            'campoSeleccionadoOrigen' => 'required',
            'infestadores' => 'required|numeric|min:0',
        ], [
            'tipo_infestacion.required' => 'El tipo de infestación es obligatorio.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser válida.',
            'campoSeleccionado.required' => 'El nombre del campo es obligatorio.',
            'area.required' => 'El área es obligatoria.',
            'area.numeric' => 'El área debe ser un número.',
            'area.min' => 'El área debe ser mayor a cero.',
            'kg_madres.required' => 'Los kilogramos de madres son obligatorios.',
            'kg_madres.numeric' => 'Los kilogramos de madres deben ser un número.',
            'campoSeleccionadoOrigen.required' => 'Elige el origen.',
            'metodo.required' => 'El método es obligatorio.',
            'metodo.string' => 'El método debe ser texto.',
            'numero_envases.required' => 'Obligatorio.',
            'numero_envases.integer' => 'El número de envases debe ser un número entero.',
            'numero_envases.min' => 'El número de envases no puede ser negativo.',
            'capacidad_envase.required' => 'Obligatorio.',
            'capacidad_envase.numeric' => 'La capacidad del envase debe ser numérica.',
            'infestadores.required' => 'Obligatorio.',
            'infestadores.numeric' => 'La cantidad de infestadores debe ser numérica.',
        ]);

        if (!$this->campania) {
            return $this->alert('error', 'No hay una campaña seleccionada');
        }
        try {

            $data = [
                'tipo_infestacion' => $this->tipo_infestacion,
                'fecha' => $this->fecha,
                'campo_nombre' => $this->campoSeleccionado,
                'area' => $this->area,
                'campo_campania_id' => $this->campania->id,
                'kg_madres' => $this->kg_madres,
                'kg_madres_por_ha' => $this->area > 0 ? ($this->kg_madres / $this->area) : null,
                'campo_origen_nombre' => $this->campoSeleccionadoOrigen,
                'metodo' => $this->metodo,
                'numero_envases' => $this->numero_envases,
                'capacidad_envase' => $this->capacidad_envase,
                'infestadores' => $this->infestadores,
                'madres_por_infestador' => $this->infestadores > 0 ? ($this->kg_madres / $this->infestadores) : null,
                'infestadores_por_ha' => $this->area > 0 ? ($this->infestadores / $this->area) : null,
            ];

            $id = InfestacionServicio::guardarInfestacion(
                $data,
                $this->kgAsignados,
                $this->cochinillaInfestacionId
            );

            $mensaje = $this->cochinillaInfestacionId ? 'Registro actualizado correctamente' : 'Registro creado correctamente';
            $this->alert('success', $mensaje);
            $this->mostrarFormulario = false;
            $this->dispatch('infestacionProcesada', ['metodo' => $this->metodo, 'id' => $id]);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }

        /*try {
            $data = [
                'tipo_infestacion' => $this->tipo_infestacion,
                'fecha' => $this->fecha,
                'campo_nombre' => $this->campoSeleccionado,
                'area' => $this->area,
                'campo_campania_id' => $this->campania->id,
                'kg_madres' => $this->kg_madres,
                'kg_madres_por_ha' => $this->area > 0 ? ($this->kg_madres / $this->area) : null,
                'campo_origen_nombre' => $this->campoSeleccionadoOrigen,
                'metodo' => $this->metodo,
                'numero_envases' => $this->numero_envases,
                'capacidad_envase' => $this->capacidad_envase,
                'infestadores' => $this->infestadores,
                'madres_por_infestador' => $this->infestadores > 0 ? ($this->kg_madres / $this->infestadores) : null,
                'infestadores_por_ha' => $this->area > 0 ? ($this->infestadores / $this->area) : null,
            ];
            $nuevoId = null;
            if ($this->cochinillaInfestacionId) {
                $cochinillaInfestacion = CochinillaInfestacion::find($this->cochinillaInfestacionId);
                if ($cochinillaInfestacion) {
                    $nuevoId = $this->cochinillaInfestacionId;
                    $cochinillaInfestacion->update($data);
                    $this->alert('success', 'Registro actualizado correctamente');
                }
            } else {
                $cochinillaInfestacion = CochinillaInfestacion::create($data);
                $nuevoId = $cochinillaInfestacion->id;
                $this->alert('success', 'Registro creado correctamente');
            }
            $this->mostrarFormulario = false;
            $this->dispatch('infestacionProcesada', ['metodo' => $this->metodo, 'id' => $nuevoId]);
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }*/
    }

    public function updatedFecha()
    {
        $this->buscarCampania();
    }
    public function updatedCampoSeleccionado($valorNuevoCampo)
    {
        $campo = Campo::where('nombre', $valorNuevoCampo)->first();

        if ($campo) {
            $this->area = $campo->area;
        } else {
            $this->area = null;
        }
        $this->buscarCampania();
    }
    public function obtenerIngresosSeleccionados($campoSeleccionado)
    {
        try {
            $fecha = $this->fecha ?? now();
            $tolerancia = 30;

            $this->cochinillaIngresoRelacionados = IngresoServicio::buscarStock(
                $campoSeleccionado,
                $this->fecha,
                30,
                $this->cochinillaInfestacionId
            )->get();

            // Solo ejecutar asignación automática si es NUEVO registro
            if (!$this->cochinillaInfestacionId) {
                if ($this->cochinillaIngresoRelacionados->count() === 1) {
                    $ingreso = $this->cochinillaIngresoRelacionados->first();
                    $kilos = $ingreso->stock_disponible ?? $ingreso->total_kilos;

                    $this->kgAsignados[$ingreso->id] = $kilos;
                    $this->kg_madres = $kilos;
                    $this->ingresosSeleccionados = [$ingreso->id];
                } else {
                    // limpiar si hay varios resultados
                    $this->kgAsignados = [];
                    $this->kg_madres = null;
                    $this->ingresosSeleccionados = [];
                }
            } else {
                // Si es edición, cargar los valores existentes
                $this->kgAsignados = [];
                $this->ingresosSeleccionados = [];

                $infestacion = CochinillaInfestacion::with('ingresos')->find($this->cochinillaInfestacionId);

                foreach ($infestacion->ingresos as $ingreso) {
                    $this->kgAsignados[$ingreso->id] = $ingreso->pivot->kg_asignados;
                    $this->ingresosSeleccionados[] = $ingreso->id;
                }
            }

        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            $this->alert('error', $th->getMessage());
        }
    }
    public function updatedCampoSeleccionadoOrigen($valorNuevoCampo)
    {
        $this->obtenerIngresosSeleccionados($valorNuevoCampo);
    }

    public function buscarCampania()
    {
        if ($this->campoSeleccionado && $this->fecha) {
            $this->campania = CampoCampania::masProximaAntesDe($this->fecha, $this->campoSeleccionado);
        } else {
            $this->campania = null;
        }
    }
    public function render()
    {
        return view('livewire.cochinilla-infestacion-form-component');
    }
}
