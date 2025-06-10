<?php

namespace App\Livewire;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Services\CampaniaServicio;
use App\Support\CalculoHelper;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class CampoCampaniaComponent extends Component
{
    use LivewireAlert;
    public $campania;
    public $campos;
    public $campoSeleccionado;
    public $hayCampaniaAnterior = false;
    public $hayCampaniaPosterior = false;
    public $resumenCosechaMadres = [];
    public $mostrarVacios;
    /**
     * Campos exclusivos para el grupo de porcentaje de acido carminico
     */
    public $porcentajeAcidoCarminicoPromedio;
    public $porcentajeAcidoCarminicoInfestadores;
    public $porcentajeAcidoCarminicoSecado;
    public $porcentajeAcidoCarminicoPodaCosechaInfestador;
    public $porcentajeAcidoCarminicoPodaCosechaLosa;
    public $porcentajeAcidoCarminicoTamanioCochinilla;

    /**
     * Campo utilizados en el grupo cosecha madres
     * 
     */
    public $grupoCosechaMadres_cosechamadres_fecha_cosecha;
    /**
     * Campo utilizado en el grupo cosecha
     */
    public $grupoCosecha_cosch_fecha;
    /**
     * Campo utilizado para el grupo de riego
     */
    public $riego_descarga_ha_hora;
    protected $listeners = [
        'GuardarInformacion',
        'confirmarEliminar',
        'poblacionPlantasEliminado' => '$refresh',
        'poblacionPlantasRegistrado' => '$refresh',
        'campaniaInsertada' => 'cargarUltimaCampania',
        'registrarDetalleCosechaMadres',
        'recargarEvaluacion' => '$refresh',
        'cosechaXCampaniaActualizada' => '$refresh'
    ];

    public function mount($campo = null)
    {
        $this->mostrarVacios = Session::get('mostrarVacios', false);
        $this->campos = Campo::orderBy('orden')->get();
        if ($campo) {
            $this->campoSeleccionado = $campo;
            Session::put('campoSeleccionado', $campo);
            $this->cargarUltimaCampania();
        } else {
            $this->campoSeleccionado = Session::get('campoSeleccionado', null);
            $this->cargarUltimaCampania();
        }
        $this->resumenCosechaMadres = $this->obtenerMapCosechaMadres();
        $this->actualizarEstadoBotones();
    }
    public function obtenerMapCosechaMadres()
    {
        if (!$this->campania)
            return [];

        $map = collect([
            ['descripcion' => 'Fecha de cosecha de madres', 'campo' => 'cosechamadres_fecha_cosecha'],
            ['descripcion' => 'Tiempo de infestación a cosecha', 'campo' => 'cosechamadres_tiempo_infestacion_a_cosecha'],

            ['descripcion' => 'Destino de madres en fresco (kg)', 'campo' => 'cosechamadres_destino_madres_fresco'],
            ['descripcion' => 'Infestador cartón - campos (kg)', 'campo' => 'cosechamadres_infestador_carton_campos'],
            ['descripcion' => 'Infestador tubo - campos (kg)', 'campo' => 'cosechamadres_infestador_tubo_campos'],
            ['descripcion' => 'Infestador mallita - campos (kg)', 'campo' => 'cosechamadres_infestador_mallita_campos'],
            ['descripcion' => 'Para secado (kg)', 'campo' => 'cosechamadres_para_secado'],
            ['descripcion' => 'Para venta en fresco (kg)', 'campo' => 'cosechamadres_para_venta_fresco'],

            ['descripcion' => 'Recuperación madres en seco', 'campo' => 'cosechamadres_recuperacion_madres'],
            ['descripcion' => 'Recuperación madres secas - cartón (kg)', 'campo' => 'cosechamadres_recuperacion_madres_seco_carton'],
            ['descripcion' => 'Recuperación madres secas - tubo (kg)', 'campo' => 'cosechamadres_recuperacion_madres_seco_tubo'],
            ['descripcion' => 'Recuperación madres secas - mallita (kg)', 'campo' => 'cosechamadres_recuperacion_madres_seco_mallita'],
            ['descripcion' => 'Recuperación madres secas - secado (kg)', 'campo' => 'cosechamadres_recuperacion_madres_seco_secado'],
            ['descripcion' => 'Recuperación madres secas - fresco (kg)', 'campo' => 'cosechamadres_recuperacion_madres_seco_fresco'],

            ['descripcion' => 'Conversión fresco a seco', 'campo' => 'cosechamadres_conversion_fresco_seco'],
            ['descripcion' => 'Conversión fresco a seco - cartón', 'campo' => 'cosechamadres_conversion_fresco_seco_carton'],
            ['descripcion' => 'Conversión fresco a seco - tubo', 'campo' => 'cosechamadres_conversion_fresco_seco_tubo'],
            ['descripcion' => 'Conversión fresco a seco - mallita', 'campo' => 'cosechamadres_conversion_fresco_seco_mallita'],
            ['descripcion' => 'Conversión fresco a seco - secado', 'campo' => 'cosechamadres_conversion_fresco_seco_secado'],
            ['descripcion' => 'Conversión fresco a seco - fresco', 'campo' => 'cosechamadres_conversion_fresco_seco_fresco'],
        ])
            ->map(function ($item) {
                $campo_ha = $item['campo'] . '_ha';
                $valor = $this->campania->{$item['campo']};
                $valor_ha = $this->campania->{$campo_ha};
                return [
                    'campo' => $item['campo'],
                    'descripcion' => $item['descripcion'],
                    'datos' => $valor,
                    'datos_ha' => number_format($valor_ha, 0), // si tienes un cálculo por ha puedes colocarlo aquí
                ];
            });

        return $map->toArray();
    }
    public function cargarResumenCosechaMadres()
    {
        $data = $this->obtenerMapCosechaMadres();
        $this->dispatch('cargarDataCosechaMadres', $data);
    }
    public function registrarDetalleCosechaMadres($datos)
    {
        $camposCalculados = [
            'cosechamadres_fecha_cosecha',
            'cosechamadres_tiempo_infestacion_a_cosecha',
            'cosechamadres_destino_madres_fresco',
            'cosechamadres_recuperacion_madres',
            'cosechamadres_conversion_fresco_seco',
            'cosechamadres_conversion_fresco_seco_carton',
            'cosechamadres_conversion_fresco_seco_tubo',
            'cosechamadres_conversion_fresco_seco_mallita',
            'cosechamadres_conversion_fresco_seco_secado',
            'cosechamadres_conversion_fresco_seco_fresco',
        ];

        // Asignar datos ingresados
        foreach ($datos as $item) {
            if (in_array($item['campo'], $camposCalculados)) {
                continue; // Saltar campos calculados
            }
            $this->campania->{$item['campo']} = (float) $item['datos'];
        }

        // Calcular conversiones fresco/seco
        $this->campania->cosechamadres_conversion_fresco_seco_carton = $this->calcularConversion(
            $this->campania->cosechamadres_infestador_carton_campos,
            $this->campania->cosechamadres_recuperacion_madres_seco_carton
        );

        $this->campania->cosechamadres_conversion_fresco_seco_tubo = $this->calcularConversion(
            $this->campania->cosechamadres_infestador_tubo_campos,
            $this->campania->cosechamadres_recuperacion_madres_seco_tubo
        );

        $this->campania->cosechamadres_conversion_fresco_seco_mallita = $this->calcularConversion(
            $this->campania->cosechamadres_infestador_mallita_campos,
            $this->campania->cosechamadres_recuperacion_madres_seco_mallita
        );

        $this->campania->cosechamadres_conversion_fresco_seco_secado = $this->calcularConversion(
            $this->campania->cosechamadres_para_secado,
            $this->campania->cosechamadres_recuperacion_madres_seco_secado
        );

        $this->campania->cosechamadres_conversion_fresco_seco_fresco = $this->calcularConversion(
            $this->campania->cosechamadres_para_venta_fresco,
            $this->campania->cosechamadres_recuperacion_madres_seco_fresco
        );

        // Guardar
        $this->campania->save();

        // Emitir evento con datos actualizados
        $data = $this->obtenerMapCosechaMadres();
        $this->dispatch('cargarDataCosechaMadres', $data);
    }
    public function registrarRiegoDescargaHa(){
        if (!$this->campania) {
            return $this->alert('error', 'Seleccione una campaña para continuar.');
        }

        $this->campania->riego_descarga_ha_hora = $this->riego_descarga_ha_hora;
        $this->campania->save();
        $this->alert('success', 'Registro de descarga por hectárea actualizado correctamente.');
    }
    private function calcularConversion($fresco, $seco)
    {
        if ($seco === null || $seco == 0) {
            return null;
        }
        return round($fresco / $seco, 0);
    }

    /*
        public function sincronizarInformacionParcial($grupo)
        {
            if (!$this->campania) {
                return $this->alert('error', 'Seleccione una campaña para continuar.');
            }

            $campaniaServicio = new CampaniaServicio($this->campania->id);

            switch ($grupo) {
                case 'cosecha_madres':
                    $campaniaServicio->registrarHistorialCosechaMadres();
                    break;
            }
            $this->campania->refresh();
            $this->cargarResumenCosechaMadres();
            $this->alert('success', 'Información sincronizada correctamente.');
        }*/
    public function sincronizarRiegos()
    {
        if (!$this->campania) {
            return $this->alert('error', 'Seleccione una campaña para continuar.');
        }

        $campaniaServicio = new CampaniaServicio($this->campania->id);
        $campaniaServicio->registrarHistorialRiegos();
        $this->campania->refresh();
        $this->dispatch('riegosSincronizados');
        $this->alert('success', 'Registro sincronizado correctamente.');
    }
    public function updatedCampoSeleccionado()
    {
        Session::put('campoSeleccionado', $this->campoSeleccionado);
        $this->cargarUltimaCampania();
        $this->actualizarEstadoBotones();
    }
    public function cargarUltimaCampania()
    {
        if (!$this->campoSeleccionado) {
            $this->campania = null;
            Session::forget('campoSeleccionado');
            return;
        }

        $campo = Campo::find($this->campoSeleccionado);

        if (!$campo) {
            return $this->alert('error', 'El campo no existe.');
        }

        $this->campania = $campo->campanias()->orderBy('fecha_inicio', 'desc')->first();
        if ($this->campania) {
            $this->grupoCosechaMadres_cosechamadres_fecha_cosecha = $this->campania->cosechamadres_fecha_cosecha;
        }
        $this->obtenerInformacionAcidoCarminico();
    }

    public function eliminarCampania($campaniaId)
    {
        $this->alert('question', '¿Está seguro(a) que desea eliminar la campaña?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Si, Eliminar',
            'cancelButtonText' => 'Cancelar',
            'onConfirmed' => 'confirmarEliminar',
            'showCancelButton' => true,
            'position' => 'center',
            'toast' => false,
            'timer' => null,
            'confirmButtonColor' => '#056A70',
            'cancelButtonColor' => '#2C2C2C',
            'data' => [
                'campaniaId' => $campaniaId,
            ],
        ]);
    }
    public function confirmarEliminar($data)
    {
        $campaniaId = $data['campaniaId'];
        $campania = CampoCampania::find($campaniaId);
        if ($campania) {
            $campaniaAnterior = CampoCampania::whereDate('fecha_inicio', '<', $campania->fecha_inicio)->orderBy('fecha_inicio')->first();
            if ($campaniaAnterior) {
                //si hay un registro anterior, debemos actualizar su fecha de fin, pero actualizaremos solo en caso haya una campaña posterior
                $campaniaPosterior = CampoCampania::whereDate('fecha_inicio', '>', $campania->fecha_inicio)->orderBy('fecha_inicio')->first();
                if ($campaniaPosterior) {
                    $fecha = Carbon::parse($campaniaPosterior->fecha_inicio)->addDay(-1);
                    $campaniaAnterior->update([
                        'fecha_fin' => $fecha
                    ]);
                } else {
                    //cuando no hay fecha siguiente o posterior, quiere decir que aun no debe haber fecha_fin
                    $campaniaAnterior->update([
                        'fecha_fin' => null
                    ]);
                }
            }
        }
        $archivos = array_filter([
            $campania->gasto_planilla_file,
            $campania->gasto_cuadrilla_file,
            $campania->gasto_resumen_bdd_file
        ]);

        // Eliminar archivos si hay rutas válidas
        if (!empty($archivos)) {
            Storage::disk('public')->delete($archivos);
        }

        $campania->delete();
        $this->cargarUltimaCampania();
        $this->alert('success', 'Registros Eliminados Correctamente.');
    }

    public function anteriorCampania()
    {
        $campaniaAnterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '<', $this->campania->fecha_inicio)
            ->orderByDesc('fecha_inicio')
            ->first();

        if ($campaniaAnterior) {
            $this->campania = $campaniaAnterior;
            $this->actualizarEstadoBotones();
        }
    }

    public function siguienteCampania()
    {
        $campaniaPosterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '>', $this->campania->fecha_inicio)
            ->orderBy('fecha_inicio')
            ->first();

        if ($campaniaPosterior) {
            $this->campania = $campaniaPosterior;
            $this->actualizarEstadoBotones();
        }
    }


    private function actualizarEstadoBotones()
    {
        if (!$this->campania || !$this->campania->fecha_inicio) {
            $this->hayCampaniaAnterior = false;
            $this->hayCampaniaPosterior = false;
            return;
        }
        $this->cargarResumenCosechaMadres();
        $this->hayCampaniaAnterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '<', $this->campania->fecha_inicio)
            ->exists();

        $this->hayCampaniaPosterior = CampoCampania::where('campo', $this->campoSeleccionado)
            ->where('fecha_inicio', '>', $this->campania->fecha_inicio)
            ->exists();
    }
    public function porcentajeAcidoCarminicoGuardar()
    {
        if (!$this->campania) {
            return;
        }

        $infest = $this->porcentajeAcidoCarminicoInfestadores;
        $secado = $this->porcentajeAcidoCarminicoSecado;
        $podaInfest = $this->porcentajeAcidoCarminicoPodaCosechaInfestador;
        $podaLosa = $this->porcentajeAcidoCarminicoPodaCosechaLosa;
        $tam = $this->porcentajeAcidoCarminicoTamanioCochinilla;

        // Calculamos el promedio (ignorando nulos)
        $valores = collect([$infest, $secado, $podaInfest, $podaLosa])->filter(fn($v) => $v !== null);
        $prom = $valores->isNotEmpty() ? $valores->avg() : null;

        $this->campania->update([
            'acid_prom' => $prom,
            'acid_infest' => $infest,
            'acid_secado' => $secado,
            'acid_poda_infest' => $podaInfest,
            'acid_poda_losa' => $podaLosa,
            'acid_tam' => $tam,
        ]);
        $this->obtenerInformacionAcidoCarminico();
        $this->alert('success', 'Registro de ácido carmínico actualizado');
    }
    public function obtenerInformacionAcidoCarminico()
    {
        if (!$this->campania) {
            return;
        }

        $this->porcentajeAcidoCarminicoPromedio = $this->campania->acid_prom;
        $this->porcentajeAcidoCarminicoInfestadores = $this->campania->acid_infest;
        $this->porcentajeAcidoCarminicoSecado = $this->campania->acid_secado;
        $this->porcentajeAcidoCarminicoPodaCosechaInfestador = $this->campania->acid_poda_infest;
        $this->porcentajeAcidoCarminicoPodaCosechaLosa = $this->campania->acid_poda_losa;
        $this->porcentajeAcidoCarminicoTamanioCochinilla = $this->campania->acid_tam;
    }
    /*
    public function registrarCambiosGrupoCosechaCosechaFecha()
    {
        if ($this->campania) {

            // Validar que la fecha no esté vacía y tenga un formato válido
            if (empty($this->grupoCosecha_cosch_fecha) || !strtotime($this->grupoCosecha_cosch_fecha)) {
                $this->campania->update([
                    'cosch_fecha' => null,
                    'cosch_tiempo_inf_cosch' => null,
                    'cosch_tiempo_reinf_cosch' => null,
                    'cosch_tiempo_ini_cosch' => null,
                ]);
                $this->alert('warning', 'La fecha de infestación se ha limpiado.');
                return;
            }

            $cosch_tiempo_inf_cosch = null;
            $cosch_tiempo_reinf_cosch = null;
            $cosch_tiempo_ini_cosch = null;


            if ($this->campania->infestacion_fecha) {

                $cosch_tiempo_inf_cosch = CalculoHelper::calcularDuracionEntreFechas(
                    $this->campania->infestacion_fecha,
                    $this->grupoCosecha_cosch_fecha
                );
            }
            if ($this->campania->reinfestacion_fecha) {
                $cosch_tiempo_reinf_cosch = CalculoHelper::calcularDuracionEntreFechas(
                    $this->campania->reinfestacion_fecha,
                    $this->grupoCosecha_cosch_fecha
                );
            }
            if ($this->campania->fecha_inicio) {
                $cosch_tiempo_ini_cosch = CalculoHelper::calcularDuracionEntreFechas(
                    $this->campania->fecha_inicio,
                    $this->grupoCosecha_cosch_fecha
                );
            }

            $this->campania->update([
                'cosch_fecha' => $this->grupoCosecha_cosch_fecha,
                'cosch_tiempo_inf_cosch' => $cosch_tiempo_inf_cosch,
                'cosch_tiempo_reinf_cosch' => $cosch_tiempo_reinf_cosch,
                'cosch_tiempo_ini_cosch' => $cosch_tiempo_ini_cosch,
            ]);

            $this->alert('success', 'Fecha de cosecha actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de cosecha');
        }
    }*/
    public function registrarCambiosCosechaFecha()
    {

        if ($this->campania) {

            // Validar que la fecha no esté vacía y tenga un formato válido
            if (empty($this->grupoCosechaMadres_cosechamadres_fecha_cosecha) || !strtotime($this->grupoCosechaMadres_cosechamadres_fecha_cosecha)) {
                $this->campania->update([
                    'cosechamadres_fecha_cosecha' => null,
                    'cosechamadres_tiempo_infestacion_a_cosecha' => null
                ]);
                $this->alert('warning', 'La fecha de infestación se ha limpiado.');
                return;
            }

            $duracion = CalculoHelper::calcularDuracionEntreFechas(
                $this->campania->infestacion_fecha,
                $this->grupoCosechaMadres_cosechamadres_fecha_cosecha
            );
            $this->campania->update([
                'cosechamadres_fecha_cosecha' => $this->grupoCosechaMadres_cosechamadres_fecha_cosecha,
                'cosechamadres_tiempo_infestacion_a_cosecha' => $duracion
            ]);
            $this->cargarResumenCosechaMadres();

            $this->alert('success', 'Fecha de cosecha madres actualizada correctamente');
        } else {
            $this->alert('error', 'No se ha podido actualizar la fecha de cosecha madres');
        }
    }
    public function render()
    {
        return view('livewire.campania-component.indice');
    }
}
