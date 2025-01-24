<?php

namespace App\Livewire;

use App\Exports\PlanillaExport;
use App\Models\Configuracion;
use App\Models\DescuentoSP;
use App\Models\DescuentoSpHistorico;
use App\Models\Empleado;
use App\Models\Grupo;
use App\Models\PlanillaAsistencia;
use App\Models\PlanillaBlanco;
use App\Models\PlanillaBlancoDetalle;
use App\Services\PlanillaServicio;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

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
     

        $this->informacionBlanco = PlanillaBlanco::where('mes', $this->mes)->where('anio', $this->anio)->first();

        if (!$this->informacionBlanco) {

            $factorRemuneracionBasica = $this->buscarDelMesAnterior();

            PlanillaBlanco::create([
                'mes' => $this->mes,
                'anio' => $this->anio,
                'factor_remuneracion_basica' => $factorRemuneracionBasica,
                'dias_laborables' => 0,
                'total_horas' => 0,
                'total_empleados' => 0
            ]);
            $this->obtenerInformacionMensual();
        } else {
            $this->diasLaborables = $this->informacionBlanco->dias_laborables;
            $this->totalHoras = $this->informacionBlanco->total_horas;
            $this->factorRemuneracionBasica = $this->informacionBlanco->factor_remuneracion_basica;
            $this->informacionBlancoDetalle = $this->informacionBlanco->detalle;
        }
    }
    public function buscarDelMesAnterior()
    {
        $mesAnterior = $this->mes - 1;
        $anioAnterior = $this->anio;

        // Si el mes es enero, cambiar al diciembre del año anterior
        if ($mesAnterior == 0) {
            $mesAnterior = 12;
            $anioAnterior--;
        }

        // Buscar la información del mes anterior
        $informacionAnterior = PlanillaBlanco::where('mes', $mesAnterior)
            ->where('anio', $anioAnterior)
            ->first();

        // Definir el valor de 'factor_remuneracion_basica'
        $factorRemuneracionBasica = $informacionAnterior
            ? ($informacionAnterior->factor_remuneracion_basica ? $informacionAnterior->factor_remuneracion_basica : (1025 / 30))  // Si existe, usar el valor anterior
            : 1025 / 30;

        return $factorRemuneracionBasica;
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
            $this->informacionBlanco->factor_remuneracion_basica = $this->factorRemuneracionBasica;
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

            $asignacionFamiliar = Configuracion::where('codigo', 'asignacion_familiar')->first();
            $configuracion = Configuracion::get()->pluck('valor', 'codigo')->toArray();
            $montoAsignacionFamiliar = $asignacionFamiliar ? $asignacionFamiliar->valor : 0;
            //$empleadosDisponibles = Empleado::with(['descuento', 'asignacionFamiliar'])->where('status', 'activo')->get()->keyBy('documento')->toArray();
            $empleadosDisponibles = Empleado::planillaAgraria()
            ->with(['descuento', 'asignacionFamiliar'])
            ->get()->keyBy('documento')->toArray();

            $planillaDetalle = PlanillaBlancoDetalle::where('planilla_blanco_id', $this->informacionBlanco->id)
                ->get(['bonificacion', 'documento'])
                ->pluck('bonificacion', 'documento');
            $fechaReferencia = Carbon::createFromDate($this->anio, $this->mes, 1)->startOfMonth();

            $codigos = ['HAB F', 'INT F', 'PRI F', 'PRO F', 'SNP', 'HAB M', 'INT M', 'PRI M', 'PRO M'];

            $descuentosAgrupados = [];
            foreach ($codigos as $codigo) {
                $descuento = DescuentoSpHistorico::where('descuento_codigo', $codigo)
                    ->where('fecha_inicio', '<=', $fechaReferencia)
                    ->with('descuentoSp')
                    ->orderBy('fecha_inicio', 'desc')
                    ->first();

                if (!$descuento) {
                    throw new \Exception("No se encontró un descuento para el código: $codigo");
                }

                $descuentosAgrupados[$codigo] = $descuento->toArray();
            }

            $asistencias = [];
            $planillaAsistencia = PlanillaAsistencia::where('mes', $this->mes)->where('anio', $this->anio)
            ->get();

            if($planillaAsistencia){
                foreach ($planillaAsistencia as $asistencia) {

                    $empleadoData = $empleadosDisponibles[$asistencia->documento] ?? null;

                    if (!$empleadoData) {
                        continue;
                        //throw new \Exception("Empleado no encontrado para el documento: {$asistencia->documento}");
                    }
                    $sppSnp = $empleadoData['descuento']['codigo'] ?? null;
                    if (!$sppSnp) {
                        throw new Exception("El empleado: " . $asistencia->nombres . " no tiene un sistema de descuento de pensiones");
                    }

                    $bonificacion = isset($planillaDetalle[$asistencia->documento]) ? $planillaDetalle[$asistencia->documento] : 0;
                    $asignacionFamiliar = count($empleadoData['asignacion_familiar']) > 0 ? $montoAsignacionFamiliar : 0;
                    $compensacionVacacional = $empleadoData['compensacion_vacacional'];
                    $descuentoSeguro = $this->obtenerDescuentoEmpleado($empleadoData, $this->anio, $this->mes);
                    $grupoColor = $this->grupoColores[$empleadoData['grupo_codigo']] ?? '#ffffff';
                    $sueldoPersonal = $empleadoData['salario'];
                    $totalHoras = isset($this->reporteTotalHorasPorMes[$empleadoData['documento']]) ? $this->reporteTotalHorasPorMes[$empleadoData['documento']] : 0;

                    $fechaNacimiento = Carbon::parse($empleadoData['fecha_nacimiento']);
                    $edad = round($fechaNacimiento->diffInYears($fechaReferencia, false));
                    
                    $asistencias[] = [
                        'dni' => $asistencia->documento,//str_starts_with($asistencia->documento, '0') ? "'{$asistencia->documento}" : $asistencia->documento, al compara con los dni al momento de obtener las hors en la tercera hoja no hace coincidencia
                        'nombres' => $empleadoData['apellido_paterno'] . ' ' . $empleadoData['apellido_materno'] . ', ' . $empleadoData['nombres'],
                        'edad' => $edad,
                        'sppSnp' => $sppSnp,
                        'bonificacion' => $bonificacion,
                        'asignacionFamiliar' => $asignacionFamiliar,
                        'compensacionVacacional' => $compensacionVacacional,
                        'descuentoSeguro' => $descuentoSeguro,
                        'grupoColor' => $grupoColor,
                        'sueldoPersonal' => $sueldoPersonal,
                        'totalHoras' => $totalHoras,
                        'estaJubilado' => $empleadoData['esta_jubilado']=='1'?'SI':'',
                        'color' => $descuentosAgrupados[$sppSnp]['descuento_sp']['color'],
                        
                    ];
                }
            }
            $asistencias = collect($asistencias)->sortBy('nombres')
            ->values()
            ->toArray();

            if (count($asistencias) == 0) {
                throw new Exception("Aún no se ha generado las asistencias");
            }
            if (!$this->informacionBlanco) {
                throw new Exception("Aún no hay información");
            }
            if (!is_numeric($this->diasLaborables) || $this->diasLaborables <= 0) {
                throw new Exception("Debe registrar un valor numérico válido para los días laborables de este mes.");
            }
            
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
            
            $horas = PlanillaAsistencia::horas($this->anio,$this->mes);
            $bonos = PlanillaServicio::obtenerBonosPlanilla($this->anio,$this->mes);
            
            $data = [
                'mes' => $this->mes,
                'anio' => $this->anio,
                'diasLaborables' => $this->diasLaborables,
                'factorRemuneracionBasica' => $this->factorRemuneracionBasica,
                'empleados' => $asistencias,
                'ctsPorcentaje' => $ctsPorcentaje,
                'gratificacionesPorcentaje' => $gratificacionesPorcentaje,
                'essaludGratificacionesPorcentaje' => $essaludGratificacionesPorcentaje,
                'rmv' => $rmv,
                'beta30Porcentaje' => $beta30Porcentaje,
                'essaludPorcentaje' => $essaludPorcentaje,
                'vidaLey' => $vidaLey,
                'vidaLeyPorcentaje' => $vidaLeyPorcentaje,
                'pensionSctr' => $pensionSctr,
                'pensionSctrPorcentaje' => $pensionSctrPorcentaje,
                'essaludEpsPorcentaje' => $essaludEpsPorcentaje,
                'porcentajeConstante' => $porcentajeConstante,
                'rem_basica_essalud' => $rem_basica_essalud,
                'descuentosAfp' => $descuentosAgrupados,
                'horas'=>$horas,
                'bonos'=>$bonos,
            ];

            $filePath = 'planilla/' . date('Y-m') . '/planilla' . '_' .
                Str::slug($this->mes . ' ' . $this->anio) .
                '.xlsx';

            Excel::store(new PlanillaExport($data), $filePath, 'public');

            $this->informacionBlanco->excel = $filePath;
            $this->informacionBlanco->save();
            PlanillaServicio::procesarExcelPlanillaDetalle($this->informacionBlanco);

            $this->obtenerInformacionMensual();
            $this->dispatch('actualizado');
            $this->dispatch("renderTable", $this->informacionBlancoDetalle);
            $this->alert('success', "Planilla generada correctamente");
        } catch (QueryException $th) {
            $this->dispatch('log', $th->getMessage());
            $this->alert('error', 'Ocurrió un error interno al generar e importar.');
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
            $documentosSinBonificacion = [];

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
                } else {
                    // Documentos con bonificación cero (solo actualizar si ya existen)
                    $documentosSinBonificacion[] = $documento;
                }
            }

            PlanillaBlancoDetalle::where('planilla_blanco_id', $this->informacionBlanco->id)
                ->whereIn('documento', $documentosSinBonificacion)
                ->update(['bonificacion' => 0]);

            $this->generarPlanilla();

            $this->alert('success', 'Registros Actualizados Correctamente (' . $calculados . ').');
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
            ->where('fecha_inicio', '<=', $fechaReferencia)
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        // Si no se encontró un descuento, asignar 0
        if (!$descuento) {
            throw new Exception("Falta Implementar los Descuento de Prima de seguro, debe implementar con fecha menor a {$fechaReferencia->format('Y-m')}");
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
