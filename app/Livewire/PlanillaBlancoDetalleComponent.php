<?php

namespace App\Livewire;

use App\Models\Configuracion;
use App\Models\DescuentoSP;
use App\Models\DescuentoSpHistorico;
use App\Models\Empleado;
use App\Models\Grupo;
use App\Models\PlanillaAsistencia;
use App\Models\PlanillaBlanco;
use App\Models\PlanillaBlancoDetalle;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class PlanillaBlancoDetalleComponent extends Component
{
    use LivewireAlert;
    public $anio;
    public $mes;
    public $informacionBlanco;
    public $meses;
    public $mesTitulo;
    public $diasLaborables;
    public $totalHoras;
    public $informacionBlancoDetalle;
    public $factorRemuneracionBasica;
    public $diasMes;
    public $descuentoColores;
    public $grupoColores;
    public $rmv;
    public $reporteTotalHorasPorMes;
    protected $listeners = ['GuardarInformacion'];
    public function mount()
    {
        $this->descuentoColores = DescuentoSP::get()->pluck("color", "codigo")->toArray();
        $this->grupoColores = Grupo::get()->pluck("color", "codigo")->toArray();


        $this->obtenerInformacionMensual();
        $this->meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    }
    public function obtenerInformacionMensual()
    {
        if (!$this->mes || !$this->anio) {
            return;
        }

        $this->reporteTotalHorasPorMes = PlanillaAsistencia::where('mes', $this->mes)->where('anio', $this->anio)->get()->pluck('total_horas', 'documento');


        $this->diasMes = Carbon::createFromDate($this->anio, $this->mes)->daysInMonth;

        $rmvObjecto = Configuracion::where('codigo', 'rmv')->first();

        if ($rmvObjecto) {
            $this->rmv = $rmvObjecto->valor;

            $this->factorRemuneracionBasica = $this->rmv / $this->diasMes;
        } else {
            $this->rmv = 1025;

            $this->factorRemuneracionBasica = $this->rmv / $this->diasMes;
        }

        $this->informacionBlanco = PlanillaBlanco::where('mes', $this->mes)->where('anio', $this->anio)->first();

        if (!$this->informacionBlanco) {
            PlanillaBlanco::create([
                'mes' => $this->mes,
                'anio' => $this->anio,
                'dias_laborables' => 0,
                'total_horas' => 0,
                'total_empleados' => 0
            ]);
            $this->obtenerInformacionMensual();
        } else {
            $this->diasLaborables = $this->informacionBlanco->dias_laborables;
            $this->totalHoras = $this->informacionBlanco->total_horas;
            $this->informacionBlancoDetalle = $this->informacionBlanco->detalle;
        }
    }
    public function render()
    {
        if ($this->mes) {
            $this->mesTitulo = $this->meses[$this->mes - 1];
        }
        return view('livewire.planilla-blanco-detalle-component');
    }
    public function guardarPlanillaDatos()
    {
        if (!$this->informacionBlanco) {
            return;
        }

        try {
            $this->informacionBlanco->dias_laborables = $this->diasLaborables;
            $this->informacionBlanco->total_horas = $this->totalHoras;
            $this->informacionBlanco->save();
            $this->alert("success", "Información actualizada con éxito");
        } catch (\Throwable $th) {
            $this->alert("error", "Ocurrió un error: " . $th->getMessage());
        }
    }
    public function updatedDiasLaborables()
    {
        if ($this->diasLaborables > 0) {
            $this->totalHoras = $this->diasLaborables * 8;
        }
    }
    public function generarPlanilla()
    {

        try {
            $asistencias = PlanillaAsistencia::where('mes', $this->mes)->where('anio', $this->anio)->get();

            if ($asistencias->count() == 0) {
                throw new Exception("Aún no se ha generado las asistencias");
            }
            if (!$this->informacionBlanco) {
                throw new Exception("Aún no hay información");
            }
            if (!is_numeric($this->diasLaborables) || $this->diasLaborables <= 0) {
                throw new Exception("Debe registrar un valor numérico válido para los días laborables de este mes.");
            }

            $empleados = Empleado::with(['descuento', 'asignacionFamiliar'])->where('status', 'activo')->get()->keyBy('documento')->toArray();
            $asignacionFamiliar = Configuracion::where('codigo', 'asignacion_familiar')->first();
            $configuracion = Configuracion::get()->pluck('valor', 'codigo')->toArray();
            $ctsPorcentaje = array_key_exists('cts_porcentaje', $configuracion) ? $configuracion['cts_porcentaje'] : 0;
            $gratificacionesPorcentaje = array_key_exists('gratificaciones', $configuracion) ? $configuracion['gratificaciones'] : 0;
            $essaludGratificacionesPorcentaje = array_key_exists('essalud_gratificaciones', $configuracion) ? $configuracion['essalud_gratificaciones'] : 0;
            $rmv = array_key_exists('rmv', $configuracion) ? $configuracion['rmv'] : 1025;
            $beta30Porcentaje = array_key_exists('beta30', $configuracion) ? $configuracion['beta30'] : 30;
            $essaludPorcentaje = array_key_exists('essalud', $configuracion) ? $configuracion['essalud'] : 6;
            $vidaLey = array_key_exists('vida_ley', $configuracion) ? $configuracion['vida_ley'] : 0;
            $vidaLeyPorcentaje = array_key_exists('vida_ley_porcentaje', $configuracion) ? $configuracion['vida_ley_porcentaje'] : 0;
            $pensionSctr = array_key_exists('pension_sctr', $configuracion) ? $configuracion['pension_sctr'] : 0;
            $pensionSctrPorcentaje = array_key_exists('pension_sctr_porcentaje', $configuracion) ? $configuracion['pension_sctr_porcentaje'] : 0;
            $essaludEpsPorcentaje = array_key_exists('essalud_eps', $configuracion) ? $configuracion['essalud_eps'] : 0;
            $porcentajeConstante = array_key_exists('porcentaje_constante', $configuracion) ? $configuracion['porcentaje_constante'] : 0;
            $rem_basica_essalud = array_key_exists('rem_basica_essalud', $configuracion) ? $configuracion['rem_basica_essalud'] : 0;

            $montoAsignacionFamiliar = $asignacionFamiliar ? $asignacionFamiliar->valor : 0;
            foreach ($asistencias as $asistencia) {

                $empleadoData = $empleados[$asistencia->documento] ?? null;

                if (!$empleadoData) {
                    continue;
                }

                $spp_snp = $empleadoData['descuento']['codigo'] ?? null;
                if (!$spp_snp) {
                    throw new Exception("El empleado: " . $asistencia->nombres . " no tiene un sistema de descuento de pensiones");
                }

                $planillaDetalle = PlanillaBlancoDetalle::where('documento', $asistencia->documento)
                    ->where('planilla_blanco_id', $this->informacionBlanco->id)
                    ->first();

                //remuneracion basica
                $remuneracionBasica = $this->factorRemuneracionBasica * $this->diasMes;

                //bonificacion                
                $bonificacion = $planillaDetalle ? $planillaDetalle->bonificacion : 0;

                //asignacion familiar
                $asignacionFamiliar = count($empleadoData['asignacion_familiar']) > 0 ? $montoAsignacionFamiliar : 0;

                //compensacion vacacional
                $compensacionVacacional = $empleadoData['compensacion_vacacional'];

                //sueldo bruto
                $sueldoBruto = $remuneracionBasica + $bonificacion + $asignacionFamiliar + $compensacionVacacional;

                //descuento prima de seguro
                $descuentoSeguro = $this->obtenerDescuentoEmpleado($empleadoData, $this->anio, $this->mes);
                $descuentoPrimaSeguro = $descuentoSeguro['descuento'] / 100 * $sueldoBruto;
                $descuentoPrimaSeguroExplicacion = $descuentoSeguro['explicacion'];


                //cts
                $cts = ($remuneracionBasica + $bonificacion + $asignacionFamiliar) * $ctsPorcentaje / 100;

                //gratificaciones
                $gratificaciones = ($remuneracionBasica + $bonificacion + $asignacionFamiliar) * $gratificacionesPorcentaje / 100;

                //essalud gratificaciones
                $essaludGratificaciones = $gratificaciones * $essaludGratificacionesPorcentaje / 100;

                //beta30
                $beta30 = $rmv * $beta30Porcentaje / 100;

                //essalud
                $essalud = $sueldoBruto * $essaludPorcentaje / 100;

                //vida ley
                $vidaLeyValor = ($sueldoBruto * $vidaLeyPorcentaje / 100) * $vidaLey;

                //pension sctr
                $pensionSctrValor = ($sueldoBruto * $pensionSctrPorcentaje / 100) * $pensionSctr;

                //essalud eps
                $essaludEps = ($sueldoBruto * $essaludEpsPorcentaje / 100) * $porcentajeConstante;

                //sueldo neto
                $sueldoNeto = ($sueldoBruto - $descuentoPrimaSeguro) + $cts + $gratificaciones + $essaludGratificaciones + $beta30;

                //rem_basica_essalud
                $rem_basica_essalud_valor = ($remuneracionBasica + $bonificacion + $asignacionFamiliar) * $rem_basica_essalud;

                //rem_basica_asg_fam_essalud_cts_grat_beta
                $rem_basica_asg_fam_essalud_cts_grat_beta = $sueldoBruto + $cts + $gratificaciones + $essaludGratificaciones + $beta30 + $essalud + $vidaLeyValor + $pensionSctrValor + $essaludEps;

                //jornal_diario
                $jornal_diario = $rem_basica_asg_fam_essalud_cts_grat_beta / $this->diasLaborables;

                //costo_hora
                $costo_hora = $jornal_diario / 8;

                //grupoColor
                $grupoColor = $this->grupoColores[$empleadoData['grupo_codigo']] ?? '#ffffff';

                //sueldo personal
                $sueldoPersonal = $empleadoData['salario'];
                $negro_diferencia_bonificacion = $sueldoPersonal - $sueldoNeto;
                $negro_sueldo_bruto = $rem_basica_asg_fam_essalud_cts_grat_beta + $negro_diferencia_bonificacion;
                $negro_sueldo_por_dia = $negro_sueldo_bruto / $this->diasLaborables;
                $negro_sueldo_por_hora = $negro_sueldo_por_dia / 8;
                $negro_diferencia_por_hora = $negro_diferencia_bonificacion / $this->totalHoras;

                $totalHoras = isset($this->reporteTotalHorasPorMes[$empleadoData['documento']]) ? $this->reporteTotalHorasPorMes[$empleadoData['documento']] : 0;
                $negro_diferencia_real = $negro_diferencia_por_hora * $totalHoras;

                PlanillaBlancoDetalle::updateOrCreate(
                    [
                        'planilla_blanco_id' => $this->informacionBlanco->id,
                        'documento' => $asistencia->documento
                    ],
                    [
                        'nombres' => $asistencia->nombres,
                        'empleado_grupo_color' => $grupoColor,
                        'orden' => $asistencia->orden,
                        'spp_snp' => $empleadoData['descuento']['codigo'],
                        'remuneracion_basica' => $remuneracionBasica,
                        'bonificacion' => $bonificacion,
                        'asignacion_familiar' => $asignacionFamiliar,
                        'compensacion_vacacional' => $compensacionVacacional,
                        'sueldo_bruto' => $sueldoBruto,
                        'dscto_afp_seguro' => $descuentoPrimaSeguro,
                        'dscto_afp_seguro_explicacion' => $descuentoPrimaSeguroExplicacion,
                        'cts' => $cts,
                        'gratificaciones' => $gratificaciones,
                        'essalud_gratificaciones' => $essaludGratificaciones,
                        'beta_30' => $beta30,
                        'essalud' => $essalud,
                        'vida_ley' => $vidaLeyValor,
                        'pension_sctr' => $pensionSctrValor,
                        'essalud_eps' => $essaludEps,
                        'sueldo_neto' => $sueldoNeto,
                        'rem_basica_essalud' => $rem_basica_essalud_valor,
                        'rem_basica_asg_fam_essalud_cts_grat_beta' => $rem_basica_asg_fam_essalud_cts_grat_beta,
                        'jornal_diario' => $jornal_diario,
                        'costo_hora' => $costo_hora,
                        'negro_diferencia_bonificacion' => $negro_diferencia_bonificacion,
                        'negro_sueldo_neto_total' => $sueldoPersonal,
                        'negro_sueldo_bruto' => $negro_sueldo_bruto,
                        'negro_sueldo_por_dia' => $negro_sueldo_por_dia,
                        'negro_sueldo_por_hora' => $negro_sueldo_por_hora,
                        'negro_diferencia_por_hora' => $negro_diferencia_por_hora,
                        'negro_diferencia_real' => $negro_diferencia_real
                    ]
                );
            }
            $this->obtenerInformacionMensual();
            $this->dispatch('actualizado');
            $this->dispatch("renderTable", $this->informacionBlancoDetalle);
            $this->alert('success', "Planilla generada correctamente");
        } catch (QueryException $th) {
            $this->alert('error', $th->getMessage());
        } catch (Exception $th) {
            $this->alert('error', $th->getMessage());
        }
    }
    public function GuardarInformacion($datos)
    {
        try {

            if (!$this->informacionBlanco) {
                throw new Exception("Aún no hay información");
            }

            $calculados = 0;

            foreach ($datos as $indice => $entry) {
                //indice 4 Bonificacion
                $documento = trim($entry[0]);
                $bonificacion = trim($entry[4]);
                if ($bonificacion > 0) {
                    PlanillaBlancoDetalle::updateOrCreate(
                        [
                            'planilla_blanco_id' => $this->informacionBlanco->id,
                            'documento' => $documento
                        ],
                        [
                            'bonificacion' => $bonificacion
                        ]
                    );
                    $calculados++;
                }
            }
            $this->alert('success', 'Registros Actualizados Correctamente ('.$calculados.').');
        } catch (Exception $ex) {
            return $this->alert('error', $ex->getMessage());
        } catch (QueryException $ex) {
            return $this->alert('error', $ex->getMessage());
        }
    }
    function obtenerDescuentoEmpleado($empleadoData, $anio, $mes)
    {
        $response = [];

        $fechaReferencia = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();

        $descuento = DescuentoSpHistorico::where('descuento_codigo', $empleadoData['descuento']['codigo'])
            ->where('fecha_inicio', '<', $fechaReferencia)
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        // Si no se encontró un descuento, asignar 0
        if (!$descuento) {
            throw new Exception("Flata Implementar los Descuento de Prima de seguro");
        }

        // Calcular la edad del empleado
        $fechaNacimiento = Carbon::parse($empleadoData['fecha_nacimiento']);
        $edad = $fechaNacimiento->diffInYears($fechaReferencia);

        // Determinar el porcentaje a aplicar
        if ($empleadoData['esta_jubilado'] == '1') {
            $response = [
                'explicacion' => 'POR SER PENSIONISTA NO TIENE RETENCIÓN',
                'descuento' => 0,
            ];
            return $response;
        } elseif ($edad > 65) {
            if ($empleadoData['descuento']['codigo'] == 'SNP') {
                $response = [
                    'explicacion' => 'MAYOR DE 65 EXONERADOS DE PRIMA, POR SER ONP NO TIENE PRIMA',
                    'descuento' => $descuento->porcentaje_65,
                ];
                return $response;
            }
            $response = [
                'explicacion' => 'MAYOR DE 65 EXONERADOS DE PRIMA',
                'descuento' => $descuento->porcentaje_65,
            ];
            return $response;
        } else {
            $response = [
                'explicacion' => '',
                'descuento' => $descuento->porcentaje,
            ];
            return $response;
        }
    }
}
