<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\CampoCampania;
use Exception;

class CampaniaServicio
{
    public $campoCampaniaId;
    public $campoCampania;
    public function __construct($campoCampaniaId = null)
    {
        $this->campoCampaniaId = $campoCampaniaId;
        if ($this->campoCampaniaId) {
            $this->campoCampania = CampoCampania::find($this->campoCampaniaId);
            if (!$this->campoCampania) {
                throw new Exception("La campaña no existe.");
            }
        }
    }
    /**
     * Actualiza los Gastos y Consumos de una determinada campaña
     * @param int $campoCampaniaId
     */
    public function actualizarGastosyConsumos()
    {
        $this->campoCampania->update([
            'gasto_planilla' => $this->gastoPlanilla(),
            'gasto_cuadrilla' => $this->gastoCuadrilla()
        ]);
    }
    public function gastoPlanilla()
    {
        return PlanillaServicio::calcularGastoPlanilla($this->campoCampaniaId);
    }
    public function gastoCuadrilla()
    {
        $query = Actividad::whereDate('fecha', '>=', $this->campoCampania->fecha_inicio);
        if ($this->campoCampania->fecha_fin) {
            $query->whereDate('fecha', '<=', $this->campoCampania->fecha_fin);
        }
        $actividades = $query->where('campo', $this->campoCampania->campo)->get();

        return $actividades->sum(function ($actividad){
            return $actividad->cuadrillero_actividades->sum(function ($cuadrilleroActividad){
                return $cuadrilleroActividad->total_bono + $cuadrilleroActividad->total_costo;
            });
        });
    }
}
