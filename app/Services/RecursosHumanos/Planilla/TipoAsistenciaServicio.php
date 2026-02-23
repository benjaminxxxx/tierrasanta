<?php

namespace App\Services\RecursosHumanos\Planilla;

use App\Models\PlanTipoAsistencia;

class TipoAsistenciaServicio
{
    public static function obtenerHorasConsideradas(string $codigoAsistencia)
    {
        try {

            // Buscar el tipo de asistencia
            $tipoAsistencia = PlanTipoAsistencia::where('codigo', $codigoAsistencia)->first();

            // Si no existe → THROW obligatorio
            if (!$tipoAsistencia) {
                throw new \Exception("El código de asistencia '{$codigoAsistencia}' no existe en PlanTipoAsistencia.");
            }

            // horas_jornal debe ser numérico; si no, retorna 0
            return is_numeric($tipoAsistencia->horas_jornal)
                ? (float) $tipoAsistencia->horas_jornal
                : 0;

        } catch (\Exception $e) {
            // Re-lanzar excepciones de dominio (como código no existente)
            throw $e;
        } catch (\Throwable $t) {
            // Cualquier otro error NO esperado retorna 0
            return 0;
        }
    }
}
