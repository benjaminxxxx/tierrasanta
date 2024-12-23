<?php

namespace App\Http\Controllers;

use App\Models\Dia;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AsistenciaPlanillaController extends Controller
{
    public function index($anio=null,$mes=null)
    {
        $data = [
            'anio'=>$anio,
            'mes'=>$mes,
        ];
        return view('planilla.horas',$data);
    }
    public function blanco()
    {
        return view('planilla.blanco');
    }
    /*
    public function cargarAsistencias(Request $request){

        $mes = $request->input('mes');
        $anio = $request->input('anio');

        $data = [
            'dias'=>[],
            'empleados'=>[]
        ];

        $data['dias'] = $this->obtenerDias($mes,$anio);
        $data['empleados'] = $this->obtenerEmpleados($mes,$anio);

        return response()->json($data);
    }
    public function obtenerEmpleados($mes,$anio)
    {
        // Obtener empleados activos y ordenarlos
        $empleados = Empleado::with('cargo')
            ->where('status', 'activo')
            ->orderBy('grupo_codigo', 'desc')
            ->orderBy('cargo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get(['id', 'documento', 'apellido_paterno', 'apellido_materno', 'nombres','cargo_id']);

        $empleados = $empleados->map(function ($empleado) {
            return [
                'documento' => $empleado->documento,
                'nombreCompleto' => "{$empleado->nombreCompleto}",
                'cargo_nombre' => $empleado->cargo->nombre?$empleado->cargo->nombre:'',
            ];
        });

        return $empleados;
    }
    public function obtenerDias($mes,$anio)
    {

        if (!$mes || !$anio) {
            return response()->json(['error' => 'Mes y año son requeridos'], 400);
        }

        $inicioMes = Carbon::create($anio, $mes, 1);
        $finMes = $inicioMes->copy()->endOfMonth();

        // Verifica si ya existen registros para el mes y año seleccionados
        $diasExistentes = Dia::where('mes', $mes)
            ->where('anio', $anio)
            ->count();

        // Si no existen registros, los crea
        if ($diasExistentes == 0) {
            while ($inicioMes <= $finMes) {
                Dia::create([
                    'dia' => $inicioMes->day,
                    'mes' => $inicioMes->month,
                    'anio' => $inicioMes->year,
                    'es_dia_no_laborable' => false, // Por defecto, no es feriado
                    'es_dia_domingo' => $inicioMes->isSunday(),
                    'observaciones' => null, // No hay observaciones por defecto
                ]);
                $inicioMes->addDay();
            }
        }

        // Carga los días del mes, incluyendo la información de la base de datos
        $dias = Dia::where('mes', $mes)
            ->where('anio', $anio)
            ->get();

        // Devuelve los días como JSON
        return $dias;
    }*/
}
