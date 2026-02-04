<?php

namespace App\Services\RecursosHumanos\Personal;

use App\Models\PlanDescuentoSp;
use App\Models\PlanEmpleado;
use Illuminate\Validation\ValidationException;

class ValidarContratoServicio
{
    public static function validarDatosExcel(array $dataExcel)
    {
        $tiposPlanilla = ['agraria', 'oficina'];
        $codigoSistemaPensiones = PlanDescuentoSp::pluck('codigo')->toArray();
        $estados = ['Continúa', 'BAJA'];
        $mapeoReal = PlanEmpleado::all()->keyBy('documento');

        $dnisFaltantes = [];
        $erroresIdentidad = [];

        foreach ($dataExcel as $indice => $fila) {
            $numeroFila = $indice + 2;
            $dni = trim($fila['dni'] ?? '');

            // --- PRIORIDAD 1: Verificar existencia de DNI ---
            if (!$mapeoReal->has($dni)) {
                // Guardamos DNI y datos para que sepas a quién registrar
                $dnisFaltantes[] = "{$dni}," .
                    trim($fila['paterno']) . "," .
                    trim($fila['materno']) . "," .
                    trim($fila['nombres']);
                continue; // Si no existe el DNI, no tiene sentido validar lo demás en esta fila
            }

            // --- PRIORIDAD 2: Validar catálogos ---
            if (!in_array(mb_strtolower($fila['planilla']), $tiposPlanilla)) {
                self::lanzarError("Fila {$numeroFila}: Planilla '{$fila['planilla']}' inválida.");
            }

            if (!in_array($fila['sistema'], $codigoSistemaPensiones)) {
                self::lanzarError("Fila {$numeroFila}: Sistema '{$fila['sistema']}' no existe.");
            }

            // --- PRIORIDAD 3: Identidad ---
            $errorId = self::obtenerErrorIdentidad($fila, $mapeoReal->get($dni), $numeroFila);
            if ($errorId) {
                $erroresIdentidad[] = $errorId;
            }
        }

        // --- LANZAR REPORTES DE ERRORES ACUMULADOS ---

        // Si hay DNIs que no existen, esto bloquea todo lo demás
        if (!empty($dnisFaltantes)) {
            $listaVertical = implode("\n", $dnisFaltantes);
            self::lanzarError(
                "LOS SIGUIENTES DNIs NO ESTÁN REGISTRADOS EN EL SISTEMA. " .
                "Regístrelos antes de continuar (puedes copiar esta lista):\n\n" .
                "DNI\tPATERNO\tMATERNO\tNOMBRES\n" .
                $listaVertical
            );
        }

        // Si todos existen pero hay nombres que no coinciden
        if (!empty($erroresIdentidad)) {
            self::lanzarError("DISCREPANCIAS DE IDENTIDAD DETECTADAS:\n\n" . implode("\n", $erroresIdentidad));
        }

        return true;
    }

    private static function obtenerErrorIdentidad(array $filaExcel, $empleadoDB, int $numeroFila)
    {
        $dni = trim($filaExcel['dni']);
        $detalles = [];

        if (self::normalizar($filaExcel['paterno']) !== self::normalizar($empleadoDB->apellido_paterno)) {
            $detalles[] = "Paterno ({$filaExcel['paterno']} vs DB: {$empleadoDB->apellido_paterno})";
        }
        if (self::normalizar($filaExcel['materno']) !== self::normalizar($empleadoDB->apellido_materno)) {
            $detalles[] = "Materno ({$filaExcel['materno']} vs DB: {$empleadoDB->apellido_materno})";
        }
        if (self::normalizar($filaExcel['nombres']) !== self::normalizar($empleadoDB->nombres)) {
            $detalles[] = "Nombres ({$filaExcel['nombres']} vs DB: {$empleadoDB->nombres})";
        }

        return !empty($detalles)
            ? "Fila {$numeroFila} [DNI {$dni}]: " . implode(", ", $detalles)
            : null;
    }

    private static function normalizar($texto)
    {
        if (!$texto)
            return '';

        // 1. Quitar espacios y convertir a mayúsculas
        $texto = mb_strtoupper(trim($texto), 'UTF-8');

        // 2. Eliminar tildes y diéresis (Transforma Á -> A, É -> E, etc.)
        // Usamos el transliterador de ICU para remover marcas de acentuación
        $trans = \Transliterator::create('Any-Latin; Latin-ASCII');
        $texto = $trans->transliterate($texto);

        return $texto;
    }

    private static function lanzarError($mensaje)
    {
        throw ValidationException::withMessages(['excel' => $mensaje]);
    }
}