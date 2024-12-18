<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanillaAsistencia extends Model
{
    use HasFactory;
    protected $fillable = [
        'grupo',
        'documento',
        'nombres',
        'total_horas',
        'mes',
        'orden',
        'anio',
    ];

    public function detalles()
    {
        return $this->hasMany(PlanillaAsistenciaDetalle::class);
    }
    public static function horas($anio,$mes)
    {
        $grupoColores = Grupo::get()->pluck("color", "codigo")->toArray();
        $tipoAsistenciaArray = TipoAsistencia::get()->mapWithKeys(function ($item) {
            return [
                $item->codigo => [
                    'color' => $item->color,
                    'descripcion' => $item->descripcion
                ]
            ];
        })->toArray();

        $ultimoDiaMes = Carbon::createFromDate($anio, $mes, 1)->endOfMonth()->day;
        $informacionAsistenciaAdicional = [];

        $empleadosDatas = Empleado::get()->keyBy('documento')->toArray();


        $empleados = self::where('mes', $mes)
            ->where('anio', $anio)
            ->with('detalles') // Traer los detalles de asistencia relacionados
            ->orderBy('grupo')
            ->get()
            ->map(function ($empleado, $indice) use ($ultimoDiaMes, $mes, $anio, &$informacionAsistenciaAdicional, $empleadosDatas,$tipoAsistenciaArray,$grupoColores) {
                // Mapea los detalles de asistencia del empleado por fecha
                $diasAsistencia = [];

                $empleadoData = $empleadosDatas[$empleado->documento] ?? null;
                
                // Inicializa el array de dias (dia_1, dia_2, ...) con null por defecto
                for ($dia = 1; $dia <= $ultimoDiaMes; $dia++) {
                    $diasAsistencia["dia_$dia"] = null; // Valor por defecto
                    $informacionAsistenciaAdicional["dia_$dia"][$empleado->documento] = [];
                }

                // Recorre los detalles de asistencia y llena los valores en los días correspondientes
                foreach ($empleado->detalles as $detalle) {
                    $fecha = Carbon::parse($detalle->fecha);
                    // Solo tomamos en cuenta los detalles que coincidan con el mes y año seleccionado
                    if ($fecha->month == $mes && $fecha->year == $anio) {
                        $diaKey = "dia_{$fecha->day}"; // Formato 'dia_1', 'dia_2', etc.
                        $diasAsistencia[$diaKey] = $detalle->horas_jornal; // O el campo que necesites
                        $informacionAsistenciaAdicional[$diaKey][$empleado->documento] = [
                            'tipo_asistencia' => $detalle->tipo_asistencia,
                            'color' => isset($tipoAsistenciaArray[$detalle->tipo_asistencia]['color'])
                                ? $tipoAsistenciaArray[$detalle->tipo_asistencia]['color']
                                : '#ffffff', // Color por defecto si no existe
                            'descripcion' => isset($tipoAsistenciaArray[$detalle->tipo_asistencia]['descripcion'])
                                ? $tipoAsistenciaArray[$detalle->tipo_asistencia]['descripcion']
                                : ''
                        ];
                    }
                }
                $grupoColor = '#ffffff';
                $grupo = '';
                if (isset($empleadoData['grupo_codigo'])) {
                    $grupoColor = $grupoColores[$empleadoData['grupo_codigo']] ?? '#ffffff';
                    $grupo = $empleadoData['grupo_codigo'];
                }

                // Retorna los datos del empleado más los días mapeados
                return array_merge([
                    'orden' => $indice + 1,
                    'grupo' => $grupo,
                    'empleado_grupo_color' => $grupoColor,
                    'documento' => $empleado->documento,
                    'nombres' => $empleado->nombres,
                    'total_horas' => $empleado->total_horas,
                ], $diasAsistencia);
            })
            ->toArray();

        return [
            'empleados'=>$empleados,
            'informacionAsistenciaAdicional'=>$informacionAsistenciaAdicional
        ];
    }
}
