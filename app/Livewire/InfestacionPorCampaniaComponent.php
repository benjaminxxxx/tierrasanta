<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Services\CampaniaServicio;
use App\Support\CalculoHelper;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Session;

class InfestacionPorCampaniaComponent extends Component
{
    use LivewireAlert;
    public $campaniaId;
    public $tipo;
    public $evaluacionesBrotesXPiso = [];
    public $infestaciones = [];
    public $ultimaInfestacion;
    public $campania;
    public $infestacion_fecha_recojo_vaciado_infestadores;
    public $reinfestacion_fecha_recojo_vaciado_infestadores;
    public $infestacion_fecha_colocacion_malla;
    public $reinfestacion_fecha_colocacion_malla;
    public $infestacion_fecha_retiro_malla;
    public $reinfestacion_fecha_retiro_malla;
    public $infestacionTexto;
    public $infestacion_fecha;
    public $reinfestacion_fecha;
    public $mostrarVacios;
    public function mount($campaniaId, $tipo = 'infestacion')
    {
        $this->mostrarVacios = Session::get('mostrarVacios',false);
        $this->campaniaId = $campaniaId;
        $this->tipo = $tipo;
        $this->infestacionTexto = $tipo == 'infestacion' ? 'infestación' : 're-infestación';
        $this->obtenerCampania();
        $this->obtenerInfestaciones();
    }
    public function obtenerCampania()
    {
        if ($this->campaniaId) {
            $this->campania = CampoCampania::find($this->campaniaId);
            $this->infestacion_fecha = $this->campania->infestacion_fecha;
        }
    }
    public function obtenerInfestaciones()
    {
        if ($this->campaniaId) {
            $this->infestaciones = CochinillaInfestacion::where('campo_campania_id', $this->campaniaId)
                ->with(['campoCampania'])
                ->where('tipo_infestacion', $this->tipo)
                ->orderBy('fecha')
                ->get();
            if ($this->tipo == 'infestacion') {
                //dd($this->campaniaId,$this->infestaciones);

            }
        }
    }
    public function sincronizarInformacion()
    {
        try {
            if (!$this->campania) {
                $this->alert('error', 'No se ha podido encontrar una campaña');
                return;
            }

            // Registrar historial
            $campaniaServicio = new CampaniaServicio($this->campania->id);
            $campaniaServicio->registrarHistorialDeInfestaciones($this->tipo);

            $this->obtenerInfestaciones();
            $this->obtenerCampania();
            $this->alert('success', 'Datos sincronizados correctamente');
        } catch (\Throwable $th) {
            $this->alert('error', $th->getMessage());
        }
    }

    public function registrarCambiosFechaRecojoVaciadoInfestadores()
    {
        if ($this->campania) {
            // Asegúrate de que ambas fechas estén como instancias de Carbon
            $fechaRecojo = Carbon::parse($this->infestacion_fecha_recojo_vaciado_infestadores);
            $fechaInfestacion = Carbon::parse($this->campania->infestacion_fecha);

            // Calcular la diferencia en días
            $diferenciaDias = $fechaInfestacion->diffInDays($fechaRecojo);

            // Actualizar la campaña
            $this->campania->update([
                'infestacion_fecha_recojo_vaciado_infestadores' => $fechaRecojo->format('Y-m-d'),
                'infestacion_permanencia_infestadores' => $diferenciaDias,
            ]);

            $this->alert('success', 'Fecha de recojo y vaciado de infestadores actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de recojo y vaciado de infestadores');
        }
    }
    public function registrarCambiosReinfestacionFecha()
    {
        if ($this->campania) {

            // Validar que la fecha no esté vacía y tenga un formato válido
            if (empty($this->reinfestacion_fecha) || !strtotime($this->reinfestacion_fecha)) {
                $this->campania->update([
                    'reinfestacion_fecha' => null,
                    'reinfestacion_duracion_desde_infestacion' => null
                ]);
                $this->alert('warning', 'La fecha de infestación se ha limpiado.');
                return;
            }

            $duracion = CalculoHelper::calcularDuracionEntreFechas($this->campania->infestacion_fecha, $this->reinfestacion_fecha);

            $this->campania->update([
                'reinfestacion_fecha' => $this->reinfestacion_fecha,
                'reinfestacion_duracion_desde_infestacion' => $duracion
            ]);

            $this->alert('success', 'Fecha de reinfestación actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de reinfestación');
        }
    }
    public function registrarCambiosFechaRecojoVaciadoReInfestadores()
    {
        if ($this->campania) {
            // Asegúrate de que ambas fechas estén como instancias de Carbon
            $fechaRecojo = Carbon::parse($this->reinfestacion_fecha_recojo_vaciado_infestadores);
            $fechaInfestacion = Carbon::parse($this->campania->reinfestacion_fecha);

            // Calcular la diferencia en días
            $diferenciaDias = $fechaInfestacion->diffInDays($fechaRecojo);

            // Actualizar la campaña
            $this->campania->update([
                'reinfestacion_fecha_recojo_vaciado_infestadores' => $fechaRecojo->format('Y-m-d'),
                'reinfestacion_permanencia_infestadores' => $diferenciaDias,
            ]);

            $this->alert('success', 'Fecha de recojo y vaciado de infestadores actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de recojo y vaciado de infestadores');
        }
    }
    public function registrarFechaColocacionMalla()
    {
        if ($this->campania) {
            // Asegúrate de que la fecha esté en formato Carbon
            $fechaColocacion = Carbon::parse($this->infestacion_fecha_colocacion_malla);
            $fechaRetiro = Carbon::parse($this->campania->infestacion_fecha_retiro_malla);

            // Calcular la diferencia en días
            $diferenciaDias = $fechaColocacion->diffInDays($fechaRetiro);

            // Actualizar la campaña
            $this->campania->update([
                'infestacion_fecha_colocacion_malla' => $fechaColocacion->format('Y-m-d'),
                'infestacion_permanencia_malla' => $diferenciaDias,
            ]);

            $this->alert('success', 'Fecha de colocación de malla actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de colocación de malla');
        }
    }
    public function registrarFechaColocacionMallaReinfestacion()
    {
        if ($this->campania) {
            // Asegúrate de que la fecha esté en formato Carbon
            $fechaColocacion = Carbon::parse($this->reinfestacion_fecha_colocacion_malla);
            $fechaRetiro = Carbon::parse($this->campania->reinfestacion_fecha_retiro_malla);

            // Calcular la diferencia en días
            $diferenciaDias = $fechaColocacion->diffInDays($fechaRetiro);

            // Actualizar la campaña
            $this->campania->update([
                'reinfestacion_fecha_colocacion_malla' => $fechaColocacion->format('Y-m-d'),
                'reinfestacion_permanencia_malla' => $diferenciaDias,
            ]);

            $this->alert('success', 'Fecha de colocación de malla actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de colocación de malla');
        }
    }
    public function registrarFechaRetiroMalla()
    {
        if ($this->campania) {
            // Asegúrate de que la fecha esté en formato Carbon
            $fechaRetiro = Carbon::parse($this->infestacion_fecha_retiro_malla);
            $fechaColocacion = Carbon::parse($this->campania->infestacion_fecha_colocacion_malla);

            // Calcular la diferencia en días
            $diferenciaDias = $fechaColocacion->diffInDays($fechaRetiro);

            // Actualizar la campaña
            $this->campania->update([
                'infestacion_fecha_retiro_malla' => $fechaRetiro->format('Y-m-d'),
                'infestacion_permanencia_malla' => $diferenciaDias,
            ]);

            $this->alert('success', 'Fecha de retiro de malla actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de retiro de malla');
        }
    }
    public function registrarFechaRetiroMallaReinfestacion()
    {
        if ($this->campania) {
            // Asegúrate de que la fecha esté en formato Carbon
            $fechaRetiro = Carbon::parse($this->reinfestacion_fecha_retiro_malla);
            $fechaColocacion = Carbon::parse($this->campania->reinfestacion_fecha_colocacion_malla);

            // Calcular la diferencia en días
            $diferenciaDias = $fechaColocacion->diffInDays($fechaRetiro);

            // Actualizar la campaña
            $this->campania->update([
                'reinfestacion_fecha_retiro_malla' => $fechaRetiro->format('Y-m-d'),
                'reinfestacion_permanencia_malla' => $diferenciaDias,
            ]);

            $this->alert('success', 'Fecha de retiro de malla actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de retiro de malla');
        }
    }
    public function render()
    {
        return view('livewire.infestacion-por-campania-component');
    }
}
