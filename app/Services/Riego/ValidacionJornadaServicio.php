<?php

namespace App\Services\Riego;

use App\Services\Campo\Gestion\CampoServicio;
use App\Support\FormatoHelper;
use Exception;
use Illuminate\Support\Carbon;

// app/Services/Riego/ValidacionJornadaServicio.php
class ValidacionJornadaServicio
{
    // Sin transaction, solo validaciones y lanzar excepciones

    public function validarCampos(array $data): array
    {
        $nombresCampos = collect($data)->pluck(0)->filter()->unique()->toArray();
        $validacion = CampoServicio::validarCamposDesdeExcel($nombresCampos);

        if (!empty($validacion['invalidos'])) {
            throw new Exception("Campos inválidos: " . implode(', ', $validacion['invalidos']));
        }

        return $validacion['filtro'];
    }

    /**
     * Valida que cada fila con datos reales tenga hora de inicio y fin
     * completas, en formato válido, y que el fin sea posterior al inicio.
     * Se ejecuta ANTES de guardar nada, para no dejar el sistema en un
     * estado inconsistente por una fila mal capturada en Handsontable.
     */
    public function validarHorarios(array $data): void
    {
        $errores = [];

        foreach ($data as $index => $row) {
            $campo = trim($row[0] ?? '');
            $horaInicio = trim($row[1] ?? '');
            $horaFin = trim($row[2] ?? '');

            // Fila completamente vacía (sin campo) -> se ignora, no es un error
            if ($campo === '' && $horaInicio === '' && $horaFin === '') {
                continue;
            }

            $numeroFila = $index + 1;

            if ($campo === '') {
                $errores[] = "Fila {$numeroFila}: falta el campo.";
                continue;
            }

            if ($horaInicio === '' || $horaFin === '') {
                $errores[] = "Fila {$numeroFila} (campo '{$campo}'): debe tener hora de inicio y hora de fin. " .
                    "Inicio: '" . ($horaInicio ?: 'vacío') . "', Fin: '" . ($horaFin ?: 'vacío') . "'.";
                continue;
            }

            try {
                $inicio = Carbon::parse(FormatoHelper::normalizarHora($horaInicio));
                $fin = Carbon::parse(FormatoHelper::normalizarHora($horaFin));
            } catch (\Throwable $e) {
                $errores[] = "Fila {$numeroFila} (campo '{$campo}'): formato de hora inválido.";
                continue;
            }

            if ($fin->lte($inicio)) {
                $errores[] = "Fila {$numeroFila} (campo '{$campo}'): la hora de fin ({$horaFin}) " .
                    "debe ser posterior a la hora de inicio ({$horaInicio}).";
            }
        }

        if (!empty($errores)) {
            throw new Exception("Se encontraron errores en los horarios:\n" . implode("\n", $errores));
        }
    }
}