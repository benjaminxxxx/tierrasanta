<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Models\CochinillaInfestacion;
use App\Services\CampaniaServicio;
use Illuminate\Support\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class InfestacionPorCampaniaComponent extends Component
{
    use LivewireAlert;
    public $evaluacionesBrotesXPiso = [];
    public $infestaciones = [];
    public $campaniaId;
    public $ultimaInfestacion;
    public $campania;
    public $infestacion_fecha_recojo_vaciado_infestadores;
    public $infestacion_fecha_colocacion_malla;
    public $infestacion_fecha_retiro_malla;
    public function mount($campaniaId)
    {
        $this->campaniaId = $campaniaId;
        $this->obtenerCampania();
        $this->obtenerInfestaciones();
    }
    public function obtenerCampania()
    {
        if ($this->campaniaId) {
            $this->campania = CampoCampania::find($this->campaniaId);
        }
    }
    public function obtenerInfestaciones()
    {
        if ($this->campaniaId) {
            $this->infestaciones = CochinillaInfestacion::where('campo_campania_id', $this->campaniaId)
                ->where('tipo_infestacion', 'infestacion')
                ->orderBy('fecha')
                ->get();
        }
    }
    public function sincronizarInformacion()
    {
        try {
            $campaniaServicio = new CampaniaServicio($this->campaniaId);
            $campaniaServicio->registrarHistorialDeInfestaciones();
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
    public function render()
    {
        return view('livewire.infestacion-por-campania-component');
    }
}
