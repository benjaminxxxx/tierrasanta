<?php

namespace App\Livewire;

use App\Models\CampoCampania;
use App\Support\CalculoHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class CosechaPorCampaniaFormComponent extends Component
{
    use LivewireAlert;

    /** @var string|null Fecha de cosecha o poda */
    public $fecha_cosecha_poda;

    /** @var float|null Tiempo de infestación a cosecha (en días) */
    public $tiempo_infestacion_a_cosecha;

    /** @var float|null Tiempo de re-infestación a cosecha (en días) */
    public $tiempo_reinfestacion_a_cosecha;

    /** @var float|null Tiempo desde el inicio hasta la cosecha (en días) */
    public $tiempo_inicio_a_cosecha;

    /** @var float|null Kg de cochinilla fresca para infestador cartón A8-B7 */
    public $kg_fresca_carton;

    /** @var float|null Kg de cochinilla fresca para infestador tubo */
    public $kg_fresca_tubo;

    /** @var float|null Kg de cochinilla fresca para infestador malla C1-2-19-l4 */
    public $kg_fresca_malla;

    /** @var float|null Kg de cochinilla fresca para losa */
    public $kg_fresca_losa;

    /** @var float|null Kg de cochinilla seca del infestador cartón */
    public $kg_seca_carton;

    /** @var float|null Kg de cochinilla seca del infestador tubo */
    public $kg_seca_tubo;

    /** @var float|null Kg de cochinilla seca del infestador malla */
    public $kg_seca_malla;

    /** @var float|null Kg de cochinilla seca de losa */
    public $kg_seca_losa;

    /** @var float|null Kg de cochinilla seca vendida como madre */
    public $kg_seca_venta_madre;

    /** @var float|null Factor de conversión fresca a seca (cartón) */
    public $factor_fresca_seca_carton;

    /** @var float|null Factor de conversión fresca a seca (tubo) */
    public $factor_fresca_seca_tubo;

    /** @var float|null Factor de conversión fresca a seca (malla) */
    public $factor_fresca_seca_malla;

    /** @var float|null Factor de conversión fresca a seca (losa) */
    public $factor_fresca_seca_losa;

    /** @var float|null Total de producción en cosecha o poda */
    public $total_produccion_cosecha_poda;

    /** @var float|null Total de producción de la campaña */
    public $total_produccion_campania;
    public $cosch_destino_carton;
    public $cosch_destino_tubo;
    public $cosch_destino_malla;
    public $campoCampania;
    public function mount($campaniaId)
    {
        $this->campoCampania = CampoCampania::find($campaniaId);
        if ($this->campoCampania) {
            $this->reasignarValores();
        }
    }
    public function reasignarValores()
    {
        if (!$this->campoCampania) {
            return;
        }
        $this->fecha_cosecha_poda = $this->campoCampania->cosch_fecha;
        $this->tiempo_infestacion_a_cosecha = $this->campoCampania->cosch_tiempo_inf_cosch;
        $this->tiempo_reinfestacion_a_cosecha = $this->campoCampania->cosch_tiempo_reinf_cosch;
        $this->tiempo_inicio_a_cosecha = $this->campoCampania->cosch_tiempo_ini_cosch;

        $this->cosch_destino_carton = $this->campoCampania->cosch_destino_carton;
        $this->cosch_destino_tubo = $this->campoCampania->cosch_destino_tubo;
        $this->cosch_destino_malla = $this->campoCampania->cosch_destino_malla;

        $this->kg_fresca_carton = $this->campoCampania->cosch_kg_fresca_carton;
        $this->kg_fresca_tubo = $this->campoCampania->cosch_kg_fresca_tubo;
        $this->kg_fresca_malla = $this->campoCampania->cosch_kg_fresca_malla;
        $this->kg_fresca_losa = $this->campoCampania->cosch_kg_fresca_losa;

        $this->kg_seca_carton = $this->campoCampania->cosch_kg_seca_carton;
        $this->kg_seca_tubo = $this->campoCampania->cosch_kg_seca_tubo;
        $this->kg_seca_malla = $this->campoCampania->cosch_kg_seca_malla;
        $this->kg_seca_losa = $this->campoCampania->cosch_kg_seca_losa;
        $this->kg_seca_venta_madre = $this->campoCampania->cosch_kg_seca_venta_madre;

        $this->factor_fresca_seca_carton = $this->campoCampania->cosch_factor_fs_carton;
        $this->factor_fresca_seca_tubo = $this->campoCampania->cosch_factor_fs_tubo;
        $this->factor_fresca_seca_malla = $this->campoCampania->cosch_factor_fs_malla;
        $this->factor_fresca_seca_losa = $this->campoCampania->cosch_factor_fs_losa;

        $this->total_produccion_cosecha_poda = $this->campoCampania->cosch_total_cosecha;
        $this->total_produccion_campania = $this->campoCampania->cosch_total_campania;
    }
    public function guardarInformacionCosecha()
    {
        if (!$this->campoCampania) {
            return $this->alert('error', 'La campaña no existe');
        }
        $cosch_fecha = $this->fecha_cosecha_poda;
        $cosch_tiempo_inf_cosch = null;
        $cosch_tiempo_reinf_cosch = null;
        $cosch_tiempo_ini_cosch = null;

        
        if ($cosch_fecha) {
            if ($this->campoCampania->infestacion_fecha) {
                
                $cosch_tiempo_inf_cosch = CalculoHelper::calcularDuracionEntreFechas(
                    $this->campoCampania->infestacion_fecha,
                    $cosch_fecha
                );
            }
            if ($this->campoCampania->reinfestacion_fecha) {
                $cosch_tiempo_reinf_cosch = CalculoHelper::calcularDuracionEntreFechas(
                    $this->campoCampania->reinfestacion_fecha,
                    $cosch_fecha
                );
            }
            if ($this->campoCampania->fecha_inicio) {
                $cosch_tiempo_ini_cosch = CalculoHelper::calcularDuracionEntreFechas(
                    $this->campoCampania->fecha_inicio,
                    $cosch_fecha
                );
            }


        }


        $cosch_kg_fresca_carton = $this->kg_fresca_carton;
        $cosch_kg_fresca_tubo = $this->kg_fresca_tubo;
        $cosch_kg_fresca_malla = $this->kg_fresca_malla;
        $cosch_kg_fresca_losa = $this->kg_fresca_losa;

        $cosch_kg_seca_carton = $this->kg_seca_carton;
        $cosch_kg_seca_tubo = $this->kg_seca_tubo;
        $cosch_kg_seca_malla = $this->kg_seca_malla;
        $cosch_kg_seca_losa = $this->kg_seca_losa;
        $cosch_kg_seca_venta_madre = $this->kg_seca_venta_madre;

        $cosch_factor_fs_carton = ($cosch_kg_seca_carton ?? 0) != 0 ? round($cosch_kg_fresca_carton / $cosch_kg_seca_carton, 2) : null;
        $cosch_factor_fs_tubo = ($cosch_kg_seca_tubo ?? 0) != 0 ? round($cosch_kg_fresca_tubo / $cosch_kg_seca_tubo, 2) : null;
        $cosch_factor_fs_malla = ($cosch_kg_seca_malla ?? 0) != 0 ? round($cosch_kg_fresca_malla / $cosch_kg_seca_malla, 2) : null;
        $cosch_factor_fs_losa = ($cosch_kg_seca_losa ?? 0) != 0 ? round($cosch_kg_fresca_losa / $cosch_kg_seca_losa, 2) : null;

        $cosch_total_campania = null;

        $total_kg_secos =
            ($cosch_kg_seca_carton ?? 0) +
            ($cosch_kg_seca_tubo ?? 0) +
            ($cosch_kg_seca_malla ?? 0) +
            ($cosch_kg_seca_losa ?? 0) +
            ($cosch_kg_seca_venta_madre ?? 0);

        $cosch_total_cosecha = ($this->campoCampania->area ?? 0) != 0 ? round($total_kg_secos / $this->campoCampania->area, 2) : null;
        $cosch_total_campania = $this->campoCampania->cosechamadres_recuperacion_madres;

        $this->campoCampania->update([
            'cosch_fecha' => $cosch_fecha,
            'cosch_tiempo_inf_cosch' => $cosch_tiempo_inf_cosch,
            'cosch_tiempo_reinf_cosch' => $cosch_tiempo_reinf_cosch,
            'cosch_tiempo_ini_cosch' => $cosch_tiempo_ini_cosch,
            'cosch_destino_carton' => $this->cosch_destino_carton,
            'cosch_destino_tubo' => $this->cosch_destino_tubo,
            'cosch_destino_malla' => $this->cosch_destino_malla,
            'cosch_kg_fresca_carton' => $cosch_kg_fresca_carton,
            'cosch_kg_fresca_tubo' => $cosch_kg_fresca_tubo,
            'cosch_kg_fresca_malla' => $cosch_kg_fresca_malla,
            'cosch_kg_fresca_losa' => $cosch_kg_fresca_losa,
            'cosch_kg_seca_carton' => $cosch_kg_seca_carton,
            'cosch_kg_seca_tubo' => $cosch_kg_seca_tubo,
            'cosch_kg_seca_malla' => $cosch_kg_seca_malla,
            'cosch_kg_seca_losa' => $cosch_kg_seca_losa,
            'cosch_kg_seca_venta_madre' => $cosch_kg_seca_venta_madre,
            'cosch_factor_fs_carton' => $cosch_factor_fs_carton,
            'cosch_factor_fs_tubo' => $cosch_factor_fs_tubo,
            'cosch_factor_fs_malla' => $cosch_factor_fs_malla,
            'cosch_factor_fs_losa' => $cosch_factor_fs_losa,
            'cosch_total_cosecha' => $cosch_total_cosecha,
            'cosch_total_campania' => $cosch_total_campania,
        ]);
        $this->reasignarValores();
        $this->dispatch('cosechaXCampaniaActualizada');
        $this->alert('success', 'Se registraron los datos de cosecha correctamente.');
    }

    public function render()
    {
        return view('livewire.cosecha-por-campania-form-component');
    }
}
