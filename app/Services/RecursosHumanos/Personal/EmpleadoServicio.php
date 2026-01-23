<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\Actividad;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanFamiliar;
use App\Models\PlanSueldo;
use App\Models\ReporteDiario;
use App\Services\Cuadrilla\CuadrilleroServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaEmpleadoServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaServicio;
use App\Support\ExcelHelper;
use Carbon\CarbonPeriod;
use Date;
use DB;
use Exception;
use Illuminate\Support\Carbon;

class EmpleadoServicio
{
    public static function guardarDataDesdeExcel($data)
    {
        DB::beginTransaction();

        try {
            // Mapa para relacionar DNI con ID de la base de datos (para nuevos y existentes)
            $dniToIdMap = [];

            // --- 1. PROCESAR EMPLEADOS ---
            $empleados = array_merge(
                $data['EMPLEADOS']['new'] ?? [],
                $data['EMPLEADOS']['update'] ?? [],
                $data['EMPLEADOS']['warning'] ?? []
            );

            foreach ($empleados as $item) {
                $payload = self::prepararDatos($item);
                $documento = $item['documento'];
                $nombres = "{$item['apellido_paterno']} {$item['apellido_materno']}, {$item['nombres']}";
                if (!$documento) {
                    throw new Exception("El registro con nombres {$nombres} no tiene DNI.");
                }
                $empleado = app(PlanillaEmpleadoServicio::class)
                    ->guardarPorDocumento($payload);

                $dniToIdMap[$item['documento']] = $empleado->id;
            }

            // --- 2. PROCESAR CONTRATACIONES ---
            $contrataciones = array_merge($data['CONTRATACIONES']['new'] ?? [], $data['CONTRATACIONES']['update'] ?? []);
            foreach ($contrataciones as $item) {
                $empId = $dniToIdMap[$item['documento']] ?? self::obtenerIdPorDni($item['documento']);
                $payload = self::prepararDatos($item, ['documento']);

                PlanContrato::updateOrCreate(
                    ['plan_empleado_id' => $empId, 'fecha_inicio' => $payload['fecha_inicio']],
                    $payload
                );
            }

            // --- 3. PROCESAR SUELDOS ---
            $sueldos = array_merge($data['SUELDOS']['new'] ?? [], $data['SUELDOS']['update'] ?? []);
            foreach ($sueldos as $item) {
                $empId = $dniToIdMap[$item['documento']] ?? self::obtenerIdPorDni($item['documento']);
                $payload = self::prepararDatos($item, ['documento']);

                PlanSueldo::updateOrCreate(
                    ['plan_empleado_id' => $empId, 'fecha_inicio' => $payload['fecha_inicio']],
                    $payload
                );
            }

            // --- 4. PROCESAR HIJOS ---
            $hijos = array_merge($data['HIJOS']['new'] ?? [], $data['HIJOS']['update'] ?? []);
            foreach ($hijos as $item) {
                $padreId = $dniToIdMap[$item['documento_padre']] ?? self::obtenerIdPorDni($item['documento_padre']);
                $payload = self::prepararDatos($item, ['documento_padre']);

                PlanFamiliar::updateOrCreate(
                    ['plan_empleado_id' => $padreId, 'documento' => $item['documento']],
                    $payload
                );
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Limpia llaves sobrantes y formatea fechas de Excel (números) a SQL
     */
    private static function prepararDatos(array $datos, array $quitar = []): array
    {
        // Llaves internas del Helper que no van a la DB
        $excluir = array_merge(['original_id', 'documento_anterior', 'alerta', 'changes'], $quitar);
        $limpio = array_diff_key($datos, array_flip($excluir));

        foreach ($limpio as $key => $value) {
            if (str_contains($key, 'documento')) {
                $limpio[$key] = (string)$limpio[$key];
            }
            // Si la columna es una fecha y viene como número de Excel
            if (str_contains($key, 'fecha') && is_numeric($value)) {
                $limpio[$key] = ExcelHelper::parseFecha($value, $key);
            }
            if (str_contains($key, 'esta_jubilado')) {
                $options = [
                    'NO' => false,
                    'SI' => true,
                ];
                $limpio[$key] = $options[$limpio[$key]] ?? false;
            }
            if (str_contains($key, 'esta_estudiando')) {
                $options = [
                    'NO' => false,
                    'SI' => true,
                ];
                $limpio[$key] = $options[$limpio[$key]] ?? false;
            }
        }

        return $limpio;
    }

    /**
     * Busca ID por DNI si no estaba en el mapa actual
     */
    private static function obtenerIdPorDni($dni)
    {
        $id = PlanEmpleado::where('documento', $dni)->value('id');
        if (!$id) {
            throw new Exception("El empleado con DNI $dni no existe en el sistema.");
        }
        return $id;
    }
    public static function obtenerReporteMensual($anio, $mes)
    {
        $diasMes = [];
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1);
        $diasEnMes = $fechaInicio->daysInMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, $diasEnMes);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);

        $empleados = ReporteDiario::with('detalles')->whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
        $empleadosGeneral = $empleados->keyBy('documento')->toArray();
        foreach ($periodo as $fecha) {
            $diasMes[] = $fecha;
        }
        foreach ($empleados as $empleado) {
            $documento = $empleado->documento;
            $fechaStr = $empleado->fecha;
            // Asegurar que el empleado esté registrado
            if (!isset($empleadosGeneral[$documento])) {
                $empleadosGeneral[$documento] = [
                    'documento' => $documento,
                    'nombre' => $empleado->nombre ?? '', // ajusta si necesitas más campos
                    'detalles' => [],
                ];
            }

            // Asignar directamente los detalles del día
            foreach ($empleado->detalles as $detalle) {
                $empleadosGeneral[$documento]['detalles'][$fechaStr][] = [
                    'labor' => $detalle->labor
                ];
            }
        }
        /*
        foreach ($empleadosGeneral as $indice => $empleado) {
            $documento = $empleado['documento'];
            $empleadosGeneral[$indice]['detalles'] = [];
            foreach ($periodo as $fecha) {
                $fechaStr = $fecha->format('Y-m-d');
                $asistencia = $empleados->first(function ($item) use ($fechaStr, $documento) {
                    return $item->fecha === $fechaStr && $item->documento === $documento;
                });

                $empleadosGeneral[$indice]['detalles'][$fechaStr] = [];
                if ($asistencia && $asistencia->detalles->count() > 0) {
                    foreach ($asistencia->detalles as $detalle) {
                        $empleadosGeneral[$indice]['detalles'][$fechaStr][] = [
                            'labor' => $detalle->labor
                        ];
                    }
                }

            }
        }*/
        return [
            'empleados' => $empleadosGeneral,
            'diasMes' => $diasMes
        ];
    }
    public static function cargarSearchableEmpleadosPlanilla(){
        return  PlanEmpleado::orderBy('apellido_paterno')
                    ->orderBy('apellido_materno')
                    ->orderBy('nombres')
                    ->get()
                    ->map(function ($empleado) {
                        return [
                            'name' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                            'id' => $empleado->id,
                        ];
                    })->toArray();
    }
    public static function cargarSearchableEmpleados($fecha, $tipoEmpleado = null)
    {
        //empleado o cuadrilla o ambos
        $documentosAgregados = array_keys(ConsolidadoRiego::where('fecha', $fecha)->pluck('regador_documento', 'regador_documento')->toArray());
        $trabajadores = [];
        switch ($tipoEmpleado) {
            case "empleados":
                $trabajadores = PlanEmpleado::whereNotIn('documento', $documentosAgregados)
                    ->orderBy('apellido_paterno')
                    ->orderBy('apellido_materno')
                    ->orderBy('nombres')
                    ->get()
                    ->map(function ($empleado) {
                        return [
                            'name' => $empleado->apellido_paterno . ' ' . $empleado->apellido_materno . ', ' . $empleado->nombres,
                            'id' => $empleado->documento,
                        ];
                    })->toArray();
                break;
            default:
                $trabajadores = Cuadrillero::whereNotIn('dni', $documentosAgregados)
                    ->whereNotNull('dni')
                    ->orderBy('nombres')
                    ->get(['dni as documento', 'nombres'])
                    ->map(function ($cuadrillero) {
                        return [
                            'name' => $cuadrillero->nombres,
                            'id' => $cuadrillero->documento,
                        ];
                    })->toArray();
                break;
        }
        return $trabajadores;
    }
    public static function guardarBonificaciones($actividad, $datos, $numeroRecojos)
    {
        // 2️⃣ Para cada fila de datos
        foreach ($datos as $fila) {

            $tipo = $fila['tipo'] ?? null;

            if ($tipo == 'CUADRILLA') {
                CuadrilleroServicio::guardarBonoCuadrilla($fila, $numeroRecojos, $actividad->id);
            }
            if ($tipo == 'PLANILLA') {
                PlanillaServicio::guardarBonoPlanilla($fila, $numeroRecojos, $actividad->id);
            }
        }
    }
}
