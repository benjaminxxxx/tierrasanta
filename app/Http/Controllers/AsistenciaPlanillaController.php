<?php

namespace App\Http\Controllers;

use App\Models\Dia;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AsistenciaPlanillaController extends Controller
{
    public function index(){
        $meses = collect([
            ['value' => 1, 'label' => 'Enero'],
            ['value' => 2, 'label' => 'Febrero'],
            ['value' => 3, 'label' => 'Marzo'],
            ['value' => 4, 'label' => 'Abril'],
            ['value' => 5, 'label' => 'Mayo'],
            ['value' => 6, 'label' => 'Junio'],
            ['value' => 7, 'label' => 'Julio'],
            ['value' => 8, 'label' => 'Agosto'],
            ['value' => 9, 'label' => 'Septiembre'],
            ['value' => 10, 'label' => 'Octubre'],
            ['value' => 11, 'label' => 'Noviembre'],
            ['value' => 12, 'label' => 'Diciembre'],
        ])->filter(function ($month) {
            // Filtrar meses hasta el mes actual
            return $month['value'] <= Carbon::now()->month;
        });

        return view('planilla.horas',[
            'meses'=>$meses
        ]);
    }
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
    }
}
