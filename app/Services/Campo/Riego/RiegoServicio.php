<?php

namespace App\Services\Campo\Riego;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\Cuadrillero;
use App\Models\PlanEmpleado;
use App\Models\PlanMensualDetalle;
use App\Models\ReporteDiarioRiego;
use App\Services\Campo\Gestion\CampoServicio;
use App\Services\RecursosHumanos\Personal\ActividadServicio;
use App\Services\RecursosHumanos\Planilla\PlanillaRegistroDiarioServicio;
use App\Services\Riego\VerificacionSincronizacionRiegoServicio;
use App\Support\FormatoHelper;
use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RiegoServicio
{
    /**
     * Procesa y guarda los registros diarios de riego para el personal.
     *
     * @param string $fecha
     * @param array|Collection $registrosDiarios
     * @throws Exception
     */
    public function registrarDiarioRegadores(string $fecha, $registrosDiarios): void
    {
        DB::transaction(function () use ($fecha, $registrosDiarios) {
            $fechaCarbon = Carbon::parse($fecha);
            $mes = $fechaCarbon->month;
            $anio = $fechaCarbon->year;

            $dataPlanilla = [];

            foreach ($registrosDiarios as $registro) {
                if ($registro['tipo'] === 'planilla') {
                    $planillaMensual = PlanMensualDetalle::where('plan_empleado_id', $registro['trabajador_id'])
                        ->whereHas('planillaMensual', function ($q) use ($mes, $anio) {
                            $q->where('mes', $mes)
                                ->where('anio', $anio);
                        })
                        ->first();

                    if (!$planillaMensual) {
                        throw new Exception("No se ha generado el registro mensual para {$registro['trabajador_name']} aún.");
                    }

                    $dataPlanilla[] = [
                        "plan_men_detalle_id" => $planillaMensual->id,
                        "asistencia" => "A",
                        "total_horas" => $registro['total_horas'],
                        "campo_1" => $registro['campo'],
                        "labor_1" => $registro['labor'],
                        "entrada_1" => $registro['hora_inicio'],
                        "salida_1" => $registro['hora_fin'],
                    ];
                } elseif ($registro['tipo'] === 'cuadrilla') {
                    // Lógica pendiente para cuadrilla
                }
            }

            if (!empty($dataPlanilla)) {
                app(PlanillaRegistroDiarioServicio::class)->guardarRegistrosDiarios($fecha, $dataPlanilla, 1);
            }

           

            ActividadServicio::detectarYCrearActividades($fecha);
        });
    }
    /**
     * Genera la lista de registros diarios para regadores en una fecha determinada.
     *
     * @param string|\DateTimeInterface $fecha
     * @return Collection
     */
    public function generarRegistroDiarioParaRegadores($fecha): Collection
    {
        $consolidados = ConsolidadoRiego::whereDate('fecha', $fecha)->get();
        $listaPorEnviar = collect();

        foreach ($consolidados as $item) {
            $nombre = $item->trabajador_nombre;

            $tipo = match ($item->trabajador_type) {
                Cuadrillero::class => 'cuadrilla',
                PlanEmpleado::class => 'planilla',
                default => 'desconocido'
            };

            // 1. Obtener registro acumulado si existe
            $registroAcumulado = ReporteDiarioRiego::where('consolidado_id', $item->id)
                ->where('por_acumulacion', true)
                ->first();

            // 2. Determinar la HORA INICIO que prevalece (la menor entre el consolidado/detalle y el acumulado)
            $horaInicioNormal = $item->hora_inicio ? Carbon::parse($item->hora_inicio) : null;
            $horaInicioAcumulado = $registroAcumulado ? Carbon::parse($registroAcumulado->hora_inicio) : null;

            if ($horaInicioNormal && $horaInicioAcumulado) {
                $horaInicioReal = $horaInicioNormal->lt($horaInicioAcumulado) ? $horaInicioNormal : $horaInicioAcumulado;
            } else {
                $horaInicioReal = $horaInicioNormal ?? $horaInicioAcumulado;
            }

            // Si no hay hora de inicio por ningún lado, saltamos el registro
            if (!$horaInicioReal) {
                continue;
            }

            // 3. Minutos totales a reportar en la jornada
            $minutosTotales = $item->minutos_jornal;

            // 4. Calcular la HORA FIN sumando los minutos totales a la hora inicio prevalente
            $horaFinReal = (clone $horaInicioReal)->addMinutes($minutosTotales);

            // 5. Agregar el registro estructurado a la colección
            $listaPorEnviar->push([
                'trabajador_id' => $item->trabajador_id,
                'trabajador_name' => $nombre,
                'tipo' => $tipo,
                'hora_inicio' => $horaInicioReal->format('H:i:s'),
                'hora_fin' => $horaFinReal->format('H:i:s'),
                'total_horas' => round($minutosTotales / 60, 2),
                'campo' => 'FDM',
                'labor' => 81,
            ]);
        }

        return $listaPorEnviar;
    }

    public function procesarRegistroDiario(ConsolidadoRiego $resumenRiego, string $fecha, array $data): void
    {
        // 1. Extraer nombres de campos del array (asumiendo que el campo es el índice 0)
        $nombresCampos = collect($data)
            ->pluck(0)
            ->filter()
            ->unique()
            ->toArray();

        // 2. Validación masiva usando tu lógica existente
        $validacion = CampoServicio::validarCamposDesdeExcel($nombresCampos);

        if (!empty($validacion['invalidos'])) {
            throw new Exception("Los siguientes campos/lotes no son válidos: " . implode(', ', $validacion['invalidos']));
        }

        // Obtener el mapa de alias -> nombre_real
        $mapaCampos = $validacion['filtro'];

        DB::transaction(function () use ($resumenRiego, $fecha, $data, $mapaCampos) {

            $resumenRiego->registrosDiarios()
                ->where('por_acumulacion', false)
                ->delete();

            foreach ($data as $row) {
                if (empty($row[0]))
                    continue;

                $aliasCampo = mb_strtolower(trim($row[0]));
                // Usamos el nombre real mapeado, si no existe (tsh/negro), usamos el original
                $nombreRealCampo = $mapaCampos[$aliasCampo] ?? $row[0];
                $hInicio = FormatoHelper::normalizarHora($row[1] ?? '00:00');
                $hFin = FormatoHelper::normalizarHora($row[2] ?? '00:00');

                ReporteDiarioRiego::create([
                    'consolidado_id' => $resumenRiego->id,
                    'documento' => '',
                    'regador' => '',
                    'campo' => $nombreRealCampo,
                    'hora_inicio' => $hInicio,
                    'hora_fin' => $hFin,
                    'fecha' => $fecha,
                    'sh' => isset($row[6]) ? ($row[6] ? 1 : 0) : 0,
                    'tipo_labor' => isset($row[4]) && trim($row[4]) !== '' ? $row[4] : 'Riego',
                    'descripcion' => $row[5] ?? null,
                ]);
            }

        });
    }

    public static function eliminarRegistroRegador($riegoId)
    {
        DB::transaction(function () use ($riegoId) {
            $consolidado = ConsolidadoRiego::find($riegoId);

            if (!$consolidado) {
                throw new Exception("No se encontró el registro de riego con ID {$riegoId}.");
            }

            // Verificar si este consolidado tiene minutos cedidos a otros días
            $usosCedidos = AcumulacionUso::where('consolidado_origen_id', $consolidado->id)->get();

            if ($usosCedidos->isNotEmpty()) {
                $detalle = $usosCedidos
                    ->load('consolidadoDestino')
                    ->map(
                        fn($uso) =>
                        Carbon::parse($uso->consolidadoDestino->fecha)->format('d/m/Y') .
                        ' (' . intdiv($uso->minutos_consumidos, 60) . 'h ' . ($uso->minutos_consumidos % 60) . 'm)'
                    )
                    ->join(', ');

                throw new Exception(
                    "No se puede eliminar este registro porque tiene horas acumuladas " .
                    "que fueron usadas en: {$detalle}. Desvincula esos usos primero."
                );
            }

            // Liberar usos donde este consolidado es el DESTINO (él usó horas de otros)
            AcumulacionUso::where('consolidado_destino_id', $consolidado->id)
                ->each(function ($uso) {
                    $uso->consolidadoOrigen->decrement('minutos_utilizados', $uso->minutos_consumidos);
                    $uso->delete();
                });

            // Borrar registros diarios
            $consolidado->registrosDiarios()->get()->each->delete();

            $consolidado->delete();
        });
    }
    private static function mapTipoToModel($tipo)
    {
        return match ($tipo) {
            'empleados' => \App\Models\PlanEmpleado::class,
            'cuadrilleros' => \App\Models\Cuadrillero::class,
            default => null,
        };
    }
    public static function registrarRegadoresEnFecha($fecha, $listaRegadores)
    {
        foreach ($listaRegadores as $regador) {
            self::createOrUpdateConsolidado($fecha, $regador);
        }
    }
    public static function createOrUpdateConsolidado($fecha, $regador)
    {
        $trabajadorId = $regador['id'];
        $trabajadorType = self::mapTipoToModel($regador['tipo']);

        if (!$trabajadorType) {
            throw new Exception("Tipo de trabajador inválido: {$regador['tipo']}");
        }

        $esCuadrilla = $regador['tipo'] === 'cuadrilleros';

        // Buscar por relación polimórfica real
        $consolidado = ConsolidadoRiego::where('trabajador_id', $trabajadorId)
            ->where('trabajador_type', $trabajadorType)
            ->where('fecha', $fecha)
            ->first();

        if ($consolidado) {

            // 🔄 Actualizar datos
            $consolidado->update([
                'regador_documento' => '',
                'regador_nombre' => '',
            ]);

            return $consolidado;
        }

        // 🆕 Crear nuevo consolidado
        return ConsolidadoRiego::create([
            'regador_documento' => '',
            'regador_nombre' => '',
            'fecha' => $fecha,
            'hora_inicio' => null,
            'hora_fin' => null,
            'total_horas_observaciones' => 0,
            //'total_horas_acumuladas' => 0,
            'estado' => 'noconsolidado',
            'no_acumular_horas' => $esCuadrilla,
            // Campos morph
            'trabajador_id' => $trabajadorId,
            'trabajador_type' => $trabajadorType,
        ]);
    }
}
