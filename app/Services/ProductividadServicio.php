<?php

namespace App\Services;

use App\Models\CuaAsistenciaSemanal;
use App\Models\CuaAsistenciaSemanalCuadrillero;
use App\Models\RegistroProductividad;
use App\Models\RegistroProductividadBono;
use App\Models\RegistroProductividadCantidad;
use App\Models\ReporteDiario;
use Exception;

class ProductividadServicio
{

    protected $registroProductividad;
    public $cantidadDetalles = 0;

    public function __construct($registroProductividadId)
    {
        $this->registroProductividad = RegistroProductividad::find($registroProductividadId);
        if (!$this->registroProductividad) {
            throw new Exception("Registro ha dejado de existir.");
        }
        $registroProductividadDetalles = $this->registroProductividad->detalles;
        if ($registroProductividadDetalles) {
            $this->cantidadDetalles = $registroProductividadDetalles->count();
        }
    }
    public function leerEmpleadosYCuadrilleros()
    {
        $empleados = ReporteDiario::empleadosEnFecha($this->registroProductividad->fecha, $this->registroProductividad->campo, $this->registroProductividad->labor_id);
        $cuadrilleros = [];// CuaAsistenciaSemanal::cuadrillerosEnFecha($this->registroProductividad->fecha);
        return array_merge($empleados, $cuadrilleros);
    }
    public function listarProductividadServicio()
    {

        $dataEmpleados = $this->leerEmpleadosYCuadrilleros();
        $registroProductividadDetalles = $this->registroProductividad->detalles;
        $cantidadDetalles = $registroProductividadDetalles->count();

        foreach ($dataEmpleados as $indice => $dataEmpleado) {
            $totalKg = 0;

            $bono = null;
            if ($dataEmpleado['tipo'] == 'cuadrilla') {
                $bono = $this->registroProductividad->bonos()->where('cuadrillero_id', $dataEmpleado['id'])->first();
            } else {
                $bono = $this->registroProductividad->bonos()->where('empleado_id', $dataEmpleado['id'])->first();
            }

            for ($i = 1; $i <= $cantidadDetalles; $i++) {
                $productividadDetalle = $registroProductividadDetalles->where('indice', $i)->first();
                $cantidadKg = 0;
                if ($productividadDetalle) {
                    $registroCantidad = null;
                    if ($dataEmpleado['tipo'] == 'cuadrilla') {
                        $registroCantidad = $productividadDetalle->cantidades()
                            ->where('cuadrillero_id', $dataEmpleado['id'])->first();
                    } else {

                        $registroCantidad = $productividadDetalle->cantidades()
                            ->where('empleado_id', $dataEmpleado['id'])->first();
                    }
                    if ($registroCantidad) {
                        $cantidadKg = $registroCantidad->kg;
                    }
                }

                $totalKg += $cantidadKg;
                $dataEmpleados[$indice]['actividad_' . $i] = $cantidadKg;
            }

            $dataEmpleados[$indice]['total_kg'] = $totalKg;
            $dataEmpleados[$indice]['adicional_kg'] = '-';
            $dataEmpleados[$indice]['bono'] = '-';

            if ($bono) {
                $dataEmpleados[$indice]['adicional_kg'] = $bono->kg_adicional;
                $dataEmpleados[$indice]['bono'] = $bono->bono;
            }
        }
        return $dataEmpleados;
    }
    public function registrarCantidades($datos)
    {
        $registroProductividadDetalles = $this->registroProductividad->detalles()->get();
        $cantidadDetalles = 0;

        if ($registroProductividadDetalles) {
            foreach ($registroProductividadDetalles as $registroProductividadDetalle) {
                $registroProductividadDetalle->cantidades()->delete();
            }
            $cantidadDetalles = $registroProductividadDetalles->count();
        }

        foreach ($datos as $dato) {
            $data = [];
            if ($dato['tipo'] == 'planilla') {
                $data['empleado_id'] = $dato['id'];
                $data['cuadrillero_id'] = null;
            } else {
                $data['cuadrillero_id'] = $dato['id'];
                $data['empleado_id'] = null;
            }

            for ($i = 1; $i <= $cantidadDetalles; $i++) {
                $cantidadKg = $dato['actividad_' . $i] ?? 0;

                if ($cantidadKg > 0) {

                    $productividadDetalle = $registroProductividadDetalles->where('indice', $i)->first();
                    $kg = 0;
                    if ($productividadDetalle) {
                        $kg = (float)$productividadDetalle->kg;
                        $data['registro_productividad_detalles_id'] = $productividadDetalle->id;
                    }

                    $data['kg'] = $cantidadKg;
                    $data['kg_subtotal'] = $cantidadKg - $kg;
                    RegistroProductividadCantidad::create($data);
                }
            }
        }
    }
    public function registrarBonos()
    {
        $empleadosYCuadrilleros = $this->leerEmpleadosYCuadrilleros();
        $cantidadDetalles = $this->registroProductividad->detalles->count();
        $valoracion = (float)$this->registroProductividad->valoracion->valor_kg_adicional;
        $algunCuadrillero = null; //si existe al menos un cuadrillero se debe actualizar los totales grupales

        if (!$valoracion) {
            throw new Exception("El registro no tiene valoración.");
        }

        if (!count($empleadosYCuadrilleros) > 0)
            return;

        foreach ($empleadosYCuadrilleros as $dato) {
            if (!$algunCuadrillero) {
                $algunCuadrillero = isset($dato['cua_asi_sem_cua_id']) ? $dato['cua_asi_sem_cua_id'] : null;
            }

            $bonoAcumulado = 0;
            $totalKg = 0;
            $cantidadEsperada = 0;
            $empleado_id = $dato['tipo'] == 'planilla' ? $dato['id'] : null;
            $cuadrillero_id = $dato['tipo'] != 'planilla' ? $dato['id'] : null;
            $this->registroProductividad->bonos()
                ->where('empleado_id', $empleado_id)
                ->where('cuadrillero_id', $cuadrillero_id)
                ->delete();

            for ($i = 1; $i <= $cantidadDetalles; $i++) {
                $registroProductividadDetalle = $this->registroProductividad->detalles()->where('indice', $i)->first();
                if ($registroProductividadDetalle) {
                    $registroProductividadDetalleId = $registroProductividadDetalle->id;
                    $registroProductividadCantidad = RegistroProductividadCantidad::where('empleado_id', $empleado_id)
                        ->where('cuadrillero_id', $cuadrillero_id)
                        ->where('registro_productividad_detalles_id', $registroProductividadDetalleId)
                        ->first();

                    if ($registroProductividadCantidad && $registroProductividadCantidad->kg > 0) {
                        $cantidadEsperada += $registroProductividadDetalle->kg;
                        $totalKg += $registroProductividadCantidad->kg;
                        $bonoAcumulado += (float)($registroProductividadCantidad->kg - $registroProductividadDetalle->kg) * (float)$valoracion;
                    }
                }
            }
            $totalAdicionalKg = (float)$totalKg - (float)$cantidadEsperada;

            //se requiere ver todas las actividades de ese dia
            //si un empleado realizo bono en cada actividad deben sumarse
            $actividadesMismoDia = RegistroProductividad::whereDate('fecha', $this->registroProductividad->fecha)->pluck('id')->toArray();

            if ($bonoAcumulado > 0) {
                //guardar bonificacion
                RegistroProductividadBono::create([
                    'empleado_id' => $empleado_id,
                    'cuadrillero_id' => $cuadrillero_id,
                    'kg_adicional' => $totalAdicionalKg,
                    'bono' => $bonoAcumulado,
                    'registro_productividad_id' => $this->registroProductividad->id
                ]);



                if (is_array($actividadesMismoDia) && count($actividadesMismoDia) > 0) {

                    if ($dato['tipo'] == 'planilla') {

                        $bonoAcumulado = RegistroProductividadBono::where('empleado_id', $empleado_id)->whereIn('registro_productividad_id', $actividadesMismoDia)->sum('bono');
                    } else {
                        $bonoAcumulado = RegistroProductividadBono::where('cuadrillero_id', $cuadrillero_id)->whereIn('registro_productividad_id', $actividadesMismoDia)->sum('bono');                 
                    }
                }

                if ($dato['tipo'] == 'planilla') {

                    PlanillaServicio::agregarBono($dato['dni'], $this->registroProductividad->fecha, $bonoAcumulado);
                }
            } else {
                
                $bonoAcumuladoRestante = 0;

                if ($dato['tipo'] == 'planilla') {
                    $bonoAcumuladoRestante = RegistroProductividadBono::where('empleado_id', $empleado_id)->whereIn('registro_productividad_id', $actividadesMismoDia)->sum('bono');
                   
                } else {
                    $bonoAcumuladoRestante = RegistroProductividadBono::where('cuadrillero_id', $cuadrillero_id)->whereIn('registro_productividad_id', $actividadesMismoDia)->sum('bono');
                }

                if ($dato['tipo'] == 'planilla') {
                    PlanillaServicio::quitarBono($dato['dni'], $this->registroProductividad->fecha,$bonoAcumuladoRestante);
                }
            }
        }
        
    }
}
