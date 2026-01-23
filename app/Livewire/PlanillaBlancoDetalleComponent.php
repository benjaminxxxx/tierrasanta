<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\PlanDescuentoSp;
use App\Models\PlanGrupo;
use App\Models\PlanMensual;
use App\Models\PlanMensualDetalle;
use App\Services\Modulos\Planilla\GestionPlanilla;
use App\Services\PlanillaServicio;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Support\Str;

class PlanillaBlancoDetalleComponent extends Component
{
    use LivewireAlert;
    public $mostrarDescuentosBeneficiosPlanilla = false;
    //INFORMACION DE TABLA PLANILLA BLANCO

    public $planillaMensual;
    public $mesTitulo;
    public $planillaMensualDetalle;
    public $horasExtraMaximas;
    public $porcentajeHoraExtra;
    public $porcentajeBonificacion;
    public $porcentajeAfp;
    public $porcentajeOnp;
    #region TABLA PLANILLA BLANCA
    public $mes;
    public $anio;
    public $diasLaborables;
    public $totalHoras;
    public $factorRemuneracionBasica;
    public $asignacionFamiliar;
    public $ctsPorcentaje;
    public $gratificaciones;
    public $essaludGratificaciones;
    public $rmv;
    public $beta30;
    public $essalud;
    public $vidaLey;
    public $vidaLeyPorcentaje;
    public $pensionSctr;
    public $pensionSctrPorcentaje;
    public $essaludEps;
    public $porcentajeConstante;
    public $remBasicaEssalud;
    #endregion
    public $diasMes;
    public $descuentoColores;
    public $grupoColores;
    //public $reporteTotalHorasPorMes;
    protected $listeners = ['GuardarInformacion'];
    public function mount($mes, $anio)
    {
        $this->mes = $mes;
        $this->anio = $anio;
        $this->descuentoColores = PlanDescuentoSp::get()->pluck("color", "codigo")->toArray();
        $this->grupoColores = PlanGrupo::get()->pluck("color", "codigo")->toArray();
        $this->obtenerInformacionMensual();
    }
    public function obtenerInformacionMensual()
    {
        if (!$this->mes || !$this->anio) {
            return;
        }
  
        // Días del mes
        $this->diasMes = Carbon::createFromDate($this->anio, $this->mes)->daysInMonth;

        // Buscar planilla blanca del mes
        $this->planillaMensual = PlanMensual::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->first();
        if (!$this->planillaMensual) {
            return;
        }
        $this->planillaMensualDetalle = $this->planillaMensual->detalle;

        $this->diasLaborables = $this->planillaMensual->dias_laborables;
        $this->totalHoras = $this->planillaMensual->total_horas;
        $this->factorRemuneracionBasica = $this->planillaMensual->factor_remuneracion_basica;
        $this->asignacionFamiliar = $this->planillaMensual->asignacion_familiar;
        $this->ctsPorcentaje = $this->planillaMensual->cts_porcentaje;
        $this->gratificaciones = $this->planillaMensual->gratificaciones;
        $this->essaludGratificaciones = $this->planillaMensual->essalud_gratificaciones;
        $this->rmv = $this->planillaMensual->rmv;
        $this->beta30 = $this->planillaMensual->beta30;
        $this->essalud = $this->planillaMensual->essalud;
        $this->vidaLey = $this->planillaMensual->vida_ley;
        $this->vidaLeyPorcentaje = $this->planillaMensual->vida_ley_porcentaje;
        $this->pensionSctr = $this->planillaMensual->pension_sctr;
        $this->pensionSctrPorcentaje = $this->planillaMensual->pension_sctr_porcentaje;
        $this->essaludEps = $this->planillaMensual->essalud_eps;
        $this->porcentajeConstante = $this->planillaMensual->porcentaje_constante;
        $this->remBasicaEssalud = $this->planillaMensual->rem_basica_essalud;
        $this->planillaMensualDetalle = $this->planillaMensual->detalle;
     
    }
   

    public function guardarPlanillaDatos($option = 1)
    {
        if (!$this->planillaMensual) {
            return;
        }

        try {
            if ($option == 2) {
                // 1️⃣ Traer datos desde configuración
                $configuracionDatos = Configuracion::whereIn('codigo', [
                    'asignacion_familiar',
                    'cts_porcentaje',
                    'gratificaciones',
                    'essalud_gratificaciones',
                    'rmv',
                    'beta30',
                    'essalud',
                    'vida_ley',
                    'vida_ley_porcentaje',
                    'pension_sctr',
                    'pension_sctr_porcentaje',
                    'essalud_eps',
                    'porcentaje_constante',
                    'rem_basica_essalud'
                ])->pluck('valor', 'codigo');

                // 2️⃣ Asignar valores desde configuración a las variables locales
                foreach ($configuracionDatos as $codigo => $valor) {
                    $propiedad = Str::camel($codigo);
                    if (property_exists($this, $propiedad)) {
                        $this->$propiedad = $valor;
                    }
                }
                return;
            }

            // 3️⃣ Guardar en la tabla planillas_blanco
            $this->planillaMensual->update([
                'dias_laborables' => $this->diasLaborables,
                'total_horas' => $this->totalHoras,
                'factor_remuneracion_basica' => $this->factorRemuneracionBasica,
                'asignacion_familiar' => $this->asignacionFamiliar,
                'cts_porcentaje' => $this->ctsPorcentaje,
                'gratificaciones' => $this->gratificaciones,
                'essalud_gratificaciones' => $this->essaludGratificaciones,
                'rmv' => $this->rmv,
                'beta30' => $this->beta30,
                'essalud' => $this->essalud,
                'vida_ley' => $this->vidaLey,
                'vida_ley_porcentaje' => $this->vidaLeyPorcentaje,
                'pension_sctr' => $this->pensionSctr,
                'pension_sctr_porcentaje' => $this->pensionSctrPorcentaje,
                'essalud_eps' => $this->essaludEps,
                'porcentaje_constante' => $this->porcentajeConstante,
                'rem_basica_essalud' => $this->remBasicaEssalud,
            ]);

            if ($option == 3) {
                // 4️⃣ Guardar también en configuración como predeterminado
                $configuracionDatos = [
                    'asignacion_familiar' => $this->asignacionFamiliar,
                    'cts_porcentaje' => $this->ctsPorcentaje,
                    'gratificaciones' => $this->gratificaciones,
                    'essalud_gratificaciones' => $this->essaludGratificaciones,
                    'rmv' => $this->rmv,
                    'beta30' => $this->beta30,
                    'essalud' => $this->essalud,
                    'vida_ley' => $this->vidaLey,
                    'vida_ley_porcentaje' => $this->vidaLeyPorcentaje,
                    'pension_sctr' => $this->pensionSctr,
                    'pension_sctr_porcentaje' => $this->pensionSctrPorcentaje,
                    'essalud_eps' => $this->essaludEps,
                    'porcentaje_constante' => $this->porcentajeConstante,
                    'rem_basica_essalud' => $this->remBasicaEssalud,
                ];

                foreach ($configuracionDatos as $codigo => $valor) {
                    Configuracion::updateOrCreate(
                        ['codigo' => $codigo],
                        ['valor' => $valor]
                    );
                }
            }
            $this->mostrarDescuentosBeneficiosPlanilla = false;
            $this->alert("success", "Información actualizada con éxito");
        } catch (\Throwable $th) {
            $this->alert("error", "Ocurrió un error: " . $th->getMessage());
        }
    }


    public function generarPlanilla()
    {
        try {
        
            $parametros = [
                'mes' => $this->mes,
                'anio' => $this->anio,

                // Variables principales
                'diasLaborables' => $this->diasLaborables,
                'factorRemuneracionBasica' => $this->factorRemuneracionBasica,

                // Porcentajes
                'asignacionFamiliar' => $this->asignacionFamiliar,
                'ctsPorcentaje' => $this->ctsPorcentaje,
                'gratificacionesPorcentaje' => $this->gratificaciones,
                'essaludGratificacionesPorcentaje' => $this->essaludGratificaciones,
                'beta30Porcentaje' => $this->beta30,
                'essaludPorcentaje' => $this->essalud,
                'vidaLeyPorcentaje' => $this->vidaLeyPorcentaje,
                'pensionSctrPorcentaje' => $this->pensionSctrPorcentaje,
                'essaludEpsPorcentaje' => $this->essaludEps,
                'porcentajeConstante' => $this->porcentajeConstante,

                // Valores fijos o montos
                'rmv' => $this->rmv,
                'vidaLey' => $this->vidaLey,
                'pensionSctr' => $this->pensionSctr,
                'essaludEps' => $this->essaludEps,
                'rem_basica_essalud' => $this->remBasicaEssalud,
            ];
            $excelPath = app(GestionPlanilla::class)->generarPlanilla($parametros);

            $this->planillaMensual->excel = $excelPath;
            $this->planillaMensual->save();
     
            PlanillaServicio::procesarExcelPlanillaDetalle($this->planillaMensual);

            $this->obtenerInformacionMensual();
            $this->dispatch('actualizado');
            $this->dispatch("renderTable", $this->planillaMensualDetalle);
            $this->alert('success', "Planilla generada correctamente");
        } catch (QueryException $th) {
            throw $th;
        }
    }
    public function generarPlanillaMensual($datos)
    {
        try {
            if (!$this->planillaMensual) {
                throw new Exception("Aún no hay información");
            }
            //guardamos las bonificaciones
            foreach ($datos as $data) {
                PlanMensualDetalle::updateOrCreate(
                    [
                        'id' => $data['id'],
                        'documento' => $data['documento'],
                        'plan_mensual_id' => $this->planillaMensual->id
                    ],
                    [
                        'bonificacion' => (float) ($data['bonificacion'] ?? 0)
                    ]
                );
            }
            
            $this->generarPlanilla();

            $this->alert('success', 'Registros Actualizados Correctamente.');
        } catch (Exception $ex) {
            return $this->alert('error', $ex->getMessage());
        } catch (QueryException $ex) {
            return $this->alert('error', $ex->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.planilla-blanco-detalle-component');
    }
}
