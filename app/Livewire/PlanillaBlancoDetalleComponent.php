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
use App\Services\RecursosHumanos\Personal\PlanillaAsistenciaServicio;
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
    //INFORMACION DE TABLA PLANILLA BLANCO

    public $informacionBlanco;
    public $meses;
    public $mesTitulo;
    public $informacionBlancoDetalle;
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

        // Total de horas por documento en asistencia
        $this->reporteTotalHorasPorMes = PlanillaAsistencia::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->get()
            ->pluck('total_horas', 'documento');

        // Días del mes
        $this->diasMes = Carbon::createFromDate($this->anio, $this->mes)->daysInMonth;

        // Buscar planilla blanca del mes
        $this->informacionBlanco = PlanillaBlanco::where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->first();

        if (!$this->informacionBlanco) {
            // Obtener valores desde configuracion
            $config = Configuracion::whereIn('codigo', [
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

            // Crear con valores por defecto
            PlanillaBlanco::create([
                'mes' => $this->mes,
                'anio' => $this->anio,
                'dias_laborables' => 0,
                'total_horas' => 0,
                'total_empleados' => 0,
                'factor_remuneracion_basica' => $this->buscarDelMesAnterior(),
                'asignacion_familiar' => $config['asignacion_familiar'] ?? 0,
                'cts_porcentaje' => $config['cts_porcentaje'] ?? 0,
                'gratificaciones' => $config['gratificaciones'] ?? 0,
                'essalud_gratificaciones' => $config['essalud_gratificaciones'] ?? 0,
                'rmv' => $config['rmv'] ?? 0,
                'beta30' => $config['beta30'] ?? 0,
                'essalud' => $config['essalud'] ?? 0,
                'vida_ley' => $config['vida_ley'] ?? 0,
                'vida_ley_porcentaje' => $config['vida_ley_porcentaje'] ?? 0,
                'pension_sctr' => $config['pension_sctr'] ?? 0,
                'pension_sctr_porcentaje' => $config['pension_sctr_porcentaje'] ?? 0,
                'essalud_eps' => $config['essalud_eps'] ?? 0,
                'porcentaje_constante' => $config['porcentaje_constante'] ?? 0,
                'rem_basica_essalud' => $config['rem_basica_essalud'] ?? 0,
            ]);

            // Volver a cargar datos recién creados
            $this->obtenerInformacionMensual();
        } else {
            // Asignar variables desde planilla_blanco
            $this->diasLaborables = $this->informacionBlanco->dias_laborables;
            $this->totalHoras = $this->informacionBlanco->total_horas;
            $this->factorRemuneracionBasica = $this->informacionBlanco->factor_remuneracion_basica;
            $this->asignacionFamiliar = $this->informacionBlanco->asignacion_familiar;
            $this->ctsPorcentaje = $this->informacionBlanco->cts_porcentaje;
            $this->gratificaciones = $this->informacionBlanco->gratificaciones;
            $this->essaludGratificaciones = $this->informacionBlanco->essalud_gratificaciones;
            $this->rmv = $this->informacionBlanco->rmv;
            $this->beta30 = $this->informacionBlanco->beta30;
            $this->essalud = $this->informacionBlanco->essalud;
            $this->vidaLey = $this->informacionBlanco->vida_ley;
            $this->vidaLeyPorcentaje = $this->informacionBlanco->vida_ley_porcentaje;
            $this->pensionSctr = $this->informacionBlanco->pension_sctr;
            $this->pensionSctrPorcentaje = $this->informacionBlanco->pension_sctr_porcentaje;
            $this->essaludEps = $this->informacionBlanco->essalud_eps;
            $this->porcentajeConstante = $this->informacionBlanco->porcentaje_constante;
            $this->remBasicaEssalud = $this->informacionBlanco->rem_basica_essalud;
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

    public function guardarPlanillaDatos($option = 1)
    {
        if (!$this->informacionBlanco) {
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
                    $propiedad = \Illuminate\Support\Str::camel($codigo);
                    if (property_exists($this, $propiedad)) {
                        $this->$propiedad = $valor;
                    }
                }
            }

            // 3️⃣ Guardar en la tabla planillas_blanco
            $this->informacionBlanco->update([
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

            $this->alert("success", "Información actualizada con éxito");
        } catch (\Throwable $th) {
            $this->alert("error", "Ocurrió un error: " . $th->getMessage());
        }
    }


    public function generarPlanilla()
    {
        try {
            //Primero que nada generar las asistencias del año y mes seleccionado
            app(PlanillaAsistenciaServicio::class)->generarResumenAsistencia($this->mes, $this->anio);

            //$configuracion = Configuracion::get()->pluck('valor', 'codigo')->toArray();

            $asignacionFamiliar = $this->asignacionFamiliar ?? 0;

            $empleadosDisponibles = Empleado::planillaAgraria($this->mes, $this->anio)
                ->with(['descuento', 'asignacionFamiliar', 'contratos', 'contratos.descuento'])
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
                    throw new Exception("No se encontró un descuento para el código: $codigo");
                }

                $descuentosAgrupados[$codigo] = $descuento->toArray();
            }

            $asistencias = [];
            $planillaAsistencia = PlanillaAsistencia::where('mes', $this->mes)->where('anio', $this->anio)
                ->get();


            if ($planillaAsistencia) {

                foreach ($planillaAsistencia as $asistencia) {

                    $empleadoData = $empleadosDisponibles[$asistencia->documento] ?? null;

                    if (!$empleadoData) {
                        continue;
                        //throw new \Exception("Empleado no encontrado para el documento: {$asistencia->documento}");
                    }

                    $nombres = $empleadoData['apellido_paterno'] . ' ' . $empleadoData['apellido_materno'] . ', ' . $empleadoData['nombres'];

                    //calcular ahora el sueldo del personal basandose en su contrato
                    $contratos = $empleadoData['contratos'];
                    $fechaReferencia = Carbon::createFromDate($this->anio, $this->mes, 1);
                    $contratoMasCercano = collect($contratos)
                        ->filter(function ($contrato) use ($fechaReferencia) {
                            // Tomar contratos que ya iniciaron antes o el mismo mes/año
                            return Carbon::parse($contrato['fecha_inicio'])->lte($fechaReferencia);
                        })
                        ->sortByDesc(function ($contrato) {
                            // Ordenar por fecha de inicio más reciente
                            return Carbon::parse($contrato['fecha_inicio']);
                        })
                        ->first();

                    if (!$contratoMasCercano) {
                        throw new Exception("El empleado {$nombres} no tiene un contrato para este mes y año seleccionado");
                    }

                    $sppSnp = $contratoMasCercano['descuento']['codigo'] ?? null;
                    if (!$sppSnp) {
                        throw new Exception("El empleado {$nombres} no tiene un sistema de descuento de pensiones");
                    }

                    //$grupoCodigo = $empleadoData['grupo_codigo']; version anterior
                    //$compensacionVacacional = $empleadoData['compensacion_vacacional'];
                    //MODULO DE CONTRATOS
                    $grupoCodigo = $contratoMasCercano['grupo_codigo'];
                    $compensacionVacacional = $contratoMasCercano['compensacion_vacacional'];
                    $estaJubilado = $contratoMasCercano['esta_jubilado'];
                    //FIN MODULO DE CONTRATOS
                    $bonificacion = isset($planillaDetalle[$asistencia->documento]) ? $planillaDetalle[$asistencia->documento] : 0;

                    //Si tiene una cantidad de hijos registros mayor a 0, asignar al valor de la asignacion familiar
                    $asignacionFamiliar = count($empleadoData['asignacion_familiar']) > 0 ? $asignacionFamiliar : 0;


                    $descuentoSeguro = $this->obtenerDescuentoEmpleado($empleadoData, $this->anio, $this->mes, $contratoMasCercano,$descuentosAgrupados);
                    $grupoColor = $this->grupoColores[$grupoCodigo] ?? '#ffffff';

                    $sueldoPersonal = $contratoMasCercano['sueldo'];
                    $totalHoras = isset($this->reporteTotalHorasPorMes[$empleadoData['documento']]) ? $this->reporteTotalHorasPorMes[$empleadoData['documento']] : 0;

                    $fechaNacimiento = Carbon::parse($empleadoData['fecha_nacimiento']);
                    $edad = round($fechaNacimiento->diffInYears($fechaReferencia, false));

                    $asistencias[] = [
                        'dni' => $asistencia->documento,//str_starts_with($asistencia->documento, '0') ? "'{$asistencia->documento}" : $asistencia->documento, al compara con los dni al momento de obtener las hors en la tercera hoja no hace coincidencia
                        'nombres' => $nombres,
                        'edad' => $edad,
                        'sppSnp' => $sppSnp,
                        'bonificacion' => $bonificacion,
                        'asignacionFamiliar' => $asignacionFamiliar,
                        'compensacionVacacional' => $compensacionVacacional,
                        'descuentoSeguro' => $descuentoSeguro,
                        'grupoColor' => $grupoColor,
                        'sueldoPersonal' => $sueldoPersonal,
                        'totalHoras' => $totalHoras,
                        'estaJubilado' => $estaJubilado == '1' ? 'SI' : '',
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
            $horas = PlanillaAsistencia::horas($this->anio, $this->mes);
            $bonos = PlanillaServicio::obtenerBonosPlanilla($this->anio, $this->mes);

            $data = [
                'mes' => $this->mes,
                'anio' => $this->anio,
                'diasLaborables' => $this->diasLaborables,
                'factorRemuneracionBasica' => $this->factorRemuneracionBasica,
                'empleados' => $asistencias,
                'ctsPorcentaje' => $this->ctsPorcentaje,// $ctsPorcentaje,
                'gratificacionesPorcentaje' => $this->gratificaciones,// $gratificacionesPorcentaje,
                'essaludGratificacionesPorcentaje' => $this->essaludGratificaciones,// $essaludGratificacionesPorcentaje,
                'rmv' => $this->rmv,// $rmv,
                'beta30Porcentaje' => $this->beta30, // $beta30Porcentaje,
                'essaludPorcentaje' => $this->essalud, // $essaludPorcentaje,
                'vidaLey' => $this->vidaLey,// $vidaLey,
                'vidaLeyPorcentaje' => $this->vidaLeyPorcentaje,// $vidaLeyPorcentaje,
                'pensionSctr' => $this->pensionSctr,// $pensionSctr,
                'pensionSctrPorcentaje' => $this->pensionSctrPorcentaje,// $pensionSctrPorcentaje,
                'essaludEpsPorcentaje' => $this->essaludEps,// $essaludEpsPorcentaje,
                'porcentajeConstante' => $this->porcentajeConstante,// $porcentajeConstante,
                'rem_basica_essalud' => $this->remBasicaEssalud,// $rem_basica_essalud,
                'descuentosAfp' => $descuentosAgrupados,
                'horas' => $horas,
                'bonos' => $bonos,
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
    function obtenerDescuentoEmpleado($empleadoData, $anio, $mes, $contrato, $descuentosAgrupados)
    {

        $fechaReferencia = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $empleadoEstaJubilado = $contrato['esta_jubilado'];

        $codigo = $contrato['descuento']['codigo'];
        $descuento = $descuentosAgrupados[$codigo] ?? null;

        if (!$descuento) {
            throw new Exception("No se encontró un descuento precargado para el código: {$codigo}");
        }

        $fechaNacimiento = Carbon::parse($empleadoData['fecha_nacimiento']);
        $edad = $fechaNacimiento->diffInYears($fechaReferencia);

        if ($empleadoEstaJubilado == '1') {
            return [
                'explicacion' => 'POR SER PENSIONISTA NO TIENE RETENCIÓN',
                'descuento' => 0,
            ];
        } elseif ($edad > 65) {
            if ($codigo == 'SNP') {
                return [
                    'explicacion' => 'MAYOR DE 65 EXONERADOS DE PRIMA, POR SER ONP NO TIENE PRIMA',
                    'descuento' => $descuento['porcentaje_65'],
                ];
            }
            return [
                'explicacion' => 'MAYOR DE 65 EXONERADOS DE PRIMA',
                'descuento' => $descuento['porcentaje_65'],
            ];
        } else {
            return [
                'explicacion' => '',
                'descuento' => $descuento['porcentaje'],
            ];
        }
    }

    /*
    function obtenerDescuentoEmpleado($empleadoData, $anio, $mes, $contrato)
    {
        $response = [];

        $fechaReferencia = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $empleadoEstaJubilado = $contrato['esta_jubilado'];

        $descuento = DescuentoSpHistorico::where('descuento_codigo', $contrato['descuento']['codigo'])
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
        if ($empleadoEstaJubilado == '1') {
            $response = [
                'explicacion' => 'POR SER PENSIONISTA NO TIENE RETENCIÓN',
                'descuento' => 0,
            ];
            return $response;
        } elseif ($edad > 65) {
            if ($contrato['descuento']['codigo'] == 'SNP') {
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
    }*/
    public function render()
    {
        if ($this->mes) {
            $this->mesTitulo = $this->meses[$this->mes - 1];
        }
        return view('livewire.planilla-blanco-detalle-component');
    }
}
