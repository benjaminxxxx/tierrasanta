<?php

namespace App\Services\Campo\Riego;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\PlanEmpleado;
use App\Models\ReporteDiarioRiego;
use App\Services\Campo\Gestion\CampoServicio;
use App\Support\FormatoHelper;
use DB;
use Exception;
use Illuminate\Support\Carbon;

class RiegoServicio
{

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
            'total_horas_riego' => 0,
            'total_horas_observaciones' => 0,
            'total_horas_acumuladas' => 0,
            'total_horas_jornal' => 0,
            'estado' => 'noconsolidado',
            'no_acumular_horas' => $esCuadrilla,
            // Campos morph
            'trabajador_id' => $trabajadorId,
            'trabajador_type' => $trabajadorType,
        ]);
    }
}
