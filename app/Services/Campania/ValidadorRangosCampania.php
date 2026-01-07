<?php
namespace App\Services\Campania;

use App\Models\Campo;
use App\Models\CampoCampania;
use DB;
use Illuminate\Support\Carbon;

class ValidadorRangosCampania
{
    public static function validarCampos(): array
    {
        $campos = Campo::all();
        $erroresPorCampo = [];

        foreach ($campos as $campo) {
            $errores = self::validarPorCampo($campo->nombre);

            if (!empty($errores)) {
                $erroresPorCampo[$campo->nombre] = [
                    'campo_nombre' => $campo->nombre,
                    'errores' => $errores,
                ];
            }
        }

        return $erroresPorCampo;
    }

    public static function validarPorCampo(string $campoNombre): array
    {
        $campanias = CampoCampania::where('campo', $campoNombre)
            ->orderBy('fecha_inicio')
            ->get();

        $errores = [];
        $hoy = Carbon::today();

        for ($i = 0; $i < $campanias->count() - 1; $i++) {
            $actual = $campanias[$i];
            $siguiente = $campanias[$i + 1];

            /**
             * Caso 1: campaña sin cierre y existe otra posterior
             */
            if (is_null($actual->fecha_fin)) {
                $errores[] = [
                    'tipo' => 'campania_sin_cierre',
                    'mensaje' => "La campaña '{$actual->nombre_campania}' no tiene fecha de cierre y existe otra posterior.",
                    'campania_id' => $actual->id,
                ];
            }

            /**
             * Caso 2: superposición
             */
            if (
                !is_null($actual->fecha_fin) &&
                $actual->fecha_fin >= $siguiente->fecha_inicio
            ) {

                /**
                 * Parche histórico:
                 * - Superposición exacta de un solo día
                 * - Fecha fin = fecha inicio
                 */

                if ($actual->fecha_fin->isSameDay($siguiente->fecha_inicio)) {

                    DB::transaction(function () use ($siguiente) {
                        $siguiente->update([
                            'fecha_inicio' => $siguiente->fecha_inicio->addDay(),
                        ]);
                    });

                    $errores[] = [
                        'tipo' => 'ajuste_automatico',
                        'mensaje' =>
                            "Se detectó superposición de fecha entre campañas. " .
                            "La fecha de inicio de '{$siguiente->nombre_campania}' fue estandarizada automáticamente (+1 día).",
                        'campania_id' => $siguiente->id,
                    ];

                    continue;
                }

                /**
                 * Superposición real (error)
                 */
                $diasSuperposicion =
                    $siguiente->fecha_inicio->diffInDays($actual->fecha_fin) + 1;

                $errores[] = [
                    'tipo' => 'superposicion',
                    'mensaje' =>
                        "La campaña '{$actual->nombre_campania}' tiene fecha de cierre el {$actual->fecha_fin->format('d/m/Y')}. " .
                        "La campaña '{$siguiente->nombre_campania}' inicia el {$siguiente->fecha_inicio->format('d/m/Y')}, " .
                        "lo que genera una superposición de {$diasSuperposicion} día(s).",
                    'campania_ids' => [$actual->id, $siguiente->id],
                ];
            }
        }

        return $errores;
    }
}
